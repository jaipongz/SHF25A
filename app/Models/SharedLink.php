<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SharedLink extends Model
{
    // Define the fillable attributes for mass assignment
    protected $fillable = [
        'item_id', // Reference to the associated item
        'shared_token', // The generated token for the shared link
        'is_active', // Whether the shared link is active or not
        'expires_at', // Optional: Expiration time for the shared link (if applicable)
    ];

    // Define the relationship with the Item model
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    // Optional: Handle the expiration logic (if needed)
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at < now();
    }
}
