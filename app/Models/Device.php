<?php

namespace App\Models;

use App\Services\DeviceService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'devices';

    /**
     * 一对一，设备属于一个分类.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DeviceCategory::class, 'category_id', 'id');
    }

    /**
     * 一对一，设备属于一个品牌.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    /**
     * 一对一，设备有一个资产追踪.
     */
    public function assetNumberTrack(): HasOne
    {
        return $this->hasOne(AssetNumberTrack::class, 'asset_number', 'asset_number');
    }

    /**
     * 远程一对一，设备有一个管理者.
     */
    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,  // 远程表
            DeviceHasUser::class,   // 中间表
            'device_id',    // 中间表对主表的关联字段
            'id',   // 远程表对中间表的关联字段
            'id',   // 主表对中间表的关联字段
            'user_id' // 中间表对远程表的关联字段
        );
    }

    /**
     * 远程一对多，设备有很多个配件.
     */
    public function parts(): HasManyThrough
    {
        return $this->hasManyThrough(
            Part::class,  // 远程表
            DeviceHasPart::class,   // 中间表
            'device_id',    // 中间表对主表的关联字段
            'id',   // 远程表对中间表的关联字段
            'id',   // 主表对中间表的关联字段
            'part_id' // 中间表对远程表的关联字段
        );
    }

    /**
     * 一对多，设备有很多工单.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'asset_number', 'asset_number');
    }

    /**
     * 远程一对多，设备有很多个配件.
     */
    public function software(): HasManyThrough
    {
        return $this->hasManyThrough(
            Software::class,  // 远程表
            DeviceHasSoftware::class,   // 中间表
            'device_id',    // 中间表对主表的关联字段
            'id',   // 远程表对中间表的关联字段
            'id',   // 主表对中间表的关联字段
            'software_id' // 中间表对远程表的关联字段
        );
    }

    /**
     * 一对多，设备有很多配件管理记录.
     */
    public function hasParts(): HasMany
    {
        return $this->hasMany(DeviceHasPart::class, 'device_id', 'id');
    }

    /**
     * 一对多，设备有很多软件管理记录.
     */
    public function hasSoftware(): HasMany
    {
        return $this->hasMany(DeviceHasSoftware::class, 'device_id', 'id');
    }

    /**
     * 一对多，设备有很多用户管理记录.
     */
    public function hasUsers(): HasMany
    {
        return $this->hasMany(DeviceHasUser::class, 'device_id', 'id');
    }

    /**
     * 模型到服务.
     */
    public function service(): DeviceService
    {
        return new DeviceService($this);
    }
}
