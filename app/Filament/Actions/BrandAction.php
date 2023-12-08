<?php

namespace App\Filament\Actions;

use App\Filament\Forms\BrandForm;
use App\Services\BrandService;
use App\Utils\LogUtil;
use App\Utils\NotificationUtil;
use Exception;
use Filament\Tables\Actions\Action;

class BrandAction
{
    /**
     * 创建品牌.
     */
    public static function create(): Action
    {
        return Action::make('新增')
            ->slideOver()
            ->icon('heroicon-m-plus')
            ->form(BrandForm::createOrEdit())
            ->action(function (array $data) {
                try {
                    $brand_service = new BrandService();
                    $brand_service->create($data);
                    NotificationUtil::make(true, '已新增品牌');
                } catch (Exception $exception) {
                    LogUtil::error($exception);
                    NotificationUtil::make(false, $exception);
                }
            })
            ->closeModalByClickingAway(false);
    }
}
