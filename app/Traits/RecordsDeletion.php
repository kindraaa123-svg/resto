<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait RecordsDeletion
{
    protected static function bootRecordsDeletion()
    {
        static::deleting(function ($model) {
            if (Auth::check() && in_array('deleted_by', $model->getFillable()) || $model->timestamps === false || property_exists($model, 'deleted_by')) {
                // We use a raw update or direct set to avoid triggering events if needed,
                // but since it's deleting (soft delete), we just set it.
                if (SchemaHasColumn($model->getTable(), 'deleted_by')) {
                    $model->deleted_by = Auth::id();
                    $model->save();
                }
            }
        });

        static::restoring(function ($model) {
            $model->deleted_by = null;
        });
    }

    /**
     * Get the user who deleted this record.
     */
    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}

/**
 * Helper to check if column exists without needing to import Schema everywhere
 */
if (! function_exists('SchemaHasColumn')) {
    function SchemaHasColumn($table, $column)
    {
        return Schema::hasColumn($table, $column);
    }
}
