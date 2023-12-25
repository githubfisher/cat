<?php

namespace App\Services;

use App\Models\Consumable;
use App\Models\Flow;
use App\Models\Setting;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\ArrayShape;

class ConsumableService extends Service
{
    public function __construct($consumable = null)
    {
        $this->model = $consumable ?? new Consumable();
    }

    /**
     * 判断是否配置报废流程.
     */
    public static function isSetRetireFlow(): bool
    {
        return Setting::query()
            ->where('custom_key', 'consumable_retire_flow_id')
            ->count();
    }

    /**
     * 新增耗材.
     *
     * @throws Exception
     */
    #[ArrayShape([
        'name' => 'string',
        'category_id' => 'int',
        'brand_id' => 'int',
        'unit_id' => 'int',
        'specification' => 'string',
        'description' => 'string',
        'image' => 'string',
        'additional' => 'string',
    ])]
    public function create(array $data): void
    {
        // 开始事务
        DB::beginTransaction();
        try {
            $this->model->setAttribute('name', $data['name']);
            $this->model->setAttribute('category_id', $data['category_id']);
            $this->model->setAttribute('brand_id', $data['brand_id']);
            $this->model->setAttribute('unit_id', $data['unit_id']);
            $this->model->setAttribute('specification', $data['specification']);
            $this->model->setAttribute('description', $data['description']);
            $this->model->setAttribute('image', $data['image']);
            $this->model->setAttribute('additional', json_encode($data['additional']));
            $this->model->setAttribute('creator_id', $data['creator_id']);
            $this->model->setAttribute('status', $data['status']);
            $this->model->save();
            // 写入事务
            DB::commit();
        } catch (Exception $exception) {
            // 回滚事务
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * 废弃.
     *
     * @throws Exception
     */
    public function retire(): void
    {
        DB::beginTransaction();
        try {
            $this->model->tracks()->delete();
            $this->model->delete();
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * 是否报废.
     */
    public function isRetired(): bool
    {
        return $this->model->getAttribute('status') == 3;
    }

    /**
     * 获取已配置的设备报废流程.
     *
     * @throws Exception
     */
    public function getRetireFlow(): Builder|Model
    {
        $flow_id = Setting::query()
            ->where('custom_key', 'consumable_retire_flow_id')
            ->value('custom_value');
        if (! $flow_id) {
            throw new Exception(__('cat/consumable.retire_flow_not_set'));
        }
        $flow = Flow::query()
            ->where('id', $flow_id)
            ->first();
        if (! $flow) {
            throw new Exception(__('cat/consumable.retire_flow_not_found'));
        }

        return $flow;
    }
}
