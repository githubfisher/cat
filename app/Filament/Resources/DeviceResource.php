<?php

namespace App\Filament\Resources;

use App\Filament\Actions\DeviceAction;
use App\Filament\Forms\DeviceForm;
use App\Filament\Imports\DeviceImporter;
use App\Filament\Resources\DeviceResource\Pages\Edit;
use App\Filament\Resources\DeviceResource\Pages\HasPart;
use App\Filament\Resources\DeviceResource\Pages\HasSoftware;
use App\Filament\Resources\DeviceResource\Pages\HasUser;
use App\Filament\Resources\DeviceResource\Pages\Index;
use App\Filament\Resources\DeviceResource\Pages\Ticket;
use App\Filament\Resources\DeviceResource\Pages\View;
use App\Models\Device;
use App\Services\DeviceCategoryService;
use App\Services\DeviceService;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Exception;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;

class DeviceResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Device::class;

    protected static ?string $navigationIcon = 'heroicon-s-server';

    protected static ?string $modelLabel = '设备';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = '资产';

    protected static ?string $recordTitleAttribute = 'asset_number';

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /* @var Device $record */
        return [
            '用户' => $record->users()->value('name'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Index::class,
            View::class,
            Edit::class,
            HasUser::class,
            HasPart::class,
            HasSoftware::class,
            Ticket::class,
        ]);
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'assign_user',
            'delete_assign_user',
            'import',
            'export',
            'retire',
            'force_retire',
            'set_auto_asset_number_rule',
            'reset_auto_asset_number_rule',
            'set_retire_flow',
            'create_has_part',
            'delete_has_part',
            'create_has_software',
            'delete_has_software',
        ];
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->toggleable()
                    ->circular()
                    ->defaultImageUrl(('/images/default.jpg'))
                    ->label('照片'),
                Tables\Columns\TextColumn::make('asset_number')
                    ->searchable()
                    ->toggleable()
                    ->label('资产编号'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->toggleable()
                    ->label('名称'),
                Tables\Columns\TextColumn::make('brand.name')
                    ->searchable()
                    ->toggleable()
                    ->label('品牌'),
                Tables\Columns\TextColumn::make('category.name')
                    ->searchable()
                    ->toggleable()
                    ->label('分类'),
                Tables\Columns\TextColumn::make('users.name')
                    ->searchable()
                    ->toggleable()
                    ->label('管理者'),
                Tables\Columns\TextColumn::make('specification')
                    ->searchable()
                    ->toggleable()
                    ->label('规格'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('category_id')
                    ->multiple()
                    ->options(DeviceCategoryService::pluckOptions())
                    ->label('分类'),
            ])
            ->actions([
                // 查看
                Tables\Actions\ViewAction::make()
                    ->visible(function () {
                        return auth()->user()->can('view_device');
                    }),
                // 编辑
                Tables\Actions\EditAction::make()
                    ->visible(function () {
                        return auth()->user()->can('update_device');
                    }),
                Tables\Actions\ActionGroup::make([
                    // 创建工单
                    DeviceAction::createTicket(),
                    // 分配管理者
                    DeviceAction::createHasUser()
                        ->visible(function (Device $device) {
                            $can = auth()->user()->can('assign_user_device');

                            return $can && ! $device->hasUsers()->count();
                        }),
                    // 解除管理者
                    DeviceAction::deleteHasUser()
                        ->visible(function (Device $device) {
                            $can = auth()->user()->can('delete_assign_user_device');

                            return $can && $device->hasUsers()->count();
                        }),
                    // 流程报废
                    DeviceAction::retire()
                        ->visible(function () {
                            $can = auth()->user()->can('retire_device');

                            return $can && DeviceService::isSetRetireFlow();
                        }),
                    // 强制报废
                    DeviceAction::forceRetire()
                        ->visible(function () {
                            return auth()->user()->can('force_retire_device');
                        }),
                ]),
            ])
            ->bulkActions([

            ])
            ->emptyStateActions([

            ])
            ->headerActions([
                // 导入
                ImportAction::make()
                    ->importer(DeviceImporter::class)
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->label('导入')
                    ->visible(auth()->user()->can('import_device')),
                // 导出
                ExportAction::make()
                    ->label('导出')
                    ->visible(auth()->user()->can('export_device')),
                // 创建
                DeviceAction::create()
                    ->visible(function () {
                        return auth()->user()->can('create_device');
                    }),
                Tables\Actions\ActionGroup::make([
                    // 前往分类
                    DeviceAction::toCategories(),
                    // 配置资产编号自动生成规则
                    DeviceAction::setAssetNumberRule()
                        ->visible(function () {
                            return auth()->user()->can('set_auto_asset_number_rule_device');
                        }),
                    // 重置资产编号自动生成规则
                    DeviceAction::resetAssetNumberRule()
                        ->visible(function () {
                            return auth()->user()->can('reset_auto_asset_number_rule_device');
                        }),
                    // 配置设备报废流程
                    DeviceAction::setRetireFlow()
                        ->visible(function () {
                            return auth()->user()->can('set_retire_flow_device');
                        }),
                ])
                    ->label('高级')
                    ->icon('heroicon-m-cog-8-tooth')
                    ->button(),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema(DeviceForm::createOrEdit());
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Group::make()->schema([
                Section::make()
                    ->schema([
                        Split::make([
                            Grid::make()
                                ->schema([
                                    Group::make([
                                        TextEntry::make('asset_number')
                                            ->label('资产编号')
                                            ->badge()
                                            ->color('primary'),
                                        TextEntry::make('name')
                                            ->label('名称'),
                                        TextEntry::make('category.name')
                                            ->label('分类'),
                                    ]),
                                    Group::make([
                                        TextEntry::make('sn')
                                            ->label('序列号'),
                                        TextEntry::make('brand.name')
                                            ->label('品牌'),
                                        TextEntry::make('specification')
                                            ->label('规格'),
                                    ]),
                                ]),
                        ]),
                    ]),
            ])->columnSpan(['lg' => 2]),
            Group::make()->schema([
                Section::make()
                    ->schema([
                        ImageEntry::make('image')
                            ->disk('public')
                            ->label('照片')
                            ->defaultImageUrl(('/images/default.jpg')),
                    ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function getPages(): array
    {
        return [
            'index' => Index::route('/'),
            'edit' => Edit::route('/{record}/edit'),
            'view' => View::route('/{record}'),
            'users' => HasUser::route('/{record}/has_users'),
            'parts' => HasPart::route('/{record}/has_parts'),
            'software' => HasSoftware::route('{record}/has_software'),
            'tickets' => Ticket::route('/{record}/has_tickets'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
