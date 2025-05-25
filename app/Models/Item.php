<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'path',
        'size',
        'user_id',
        'parent_id',
        'is_deleted',
        'deleted_at'
    ];
    protected $casts = [
        'is_deleted' => 'boolean',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Item::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Item::class, 'parent_id');
    }

    public function sharedLinks()
    {
        return $this->hasMany(SharedLink::class);
    }

    public static function softDeleteWithChildren($ids, $userId)
    {
        $itemsToDelete = Item::whereIn('id', $ids)
            ->where('user_id', $userId)
            ->get();

        foreach ($itemsToDelete as $item) {
            // ลบตัวเองแบบ soft delete
            $item->update([
                'is_deleted' => true,
                'deleted_at' => now(),
            ]);

            // หา children ทั้งหมด แล้วลบแบบ recursive
            $children = Item::where('parent_id', $item->id)
                ->where('user_id', $userId)
                ->get();

            if ($children->isNotEmpty()) {
                static::softDeleteWithChildren($children->pluck('id')->toArray(), $userId);
            }
        }
    }
}

