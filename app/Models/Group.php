<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'active',
        'image',
        'slug',
        'created_by'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($group) {
            $group->slug = Str::slug($group->name);
        });

        static::updating(function ($group) {
            if ($group->isDirty('name')) {
                $group->slug = Str::slug($group->name);
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'group_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function trainTableSelections()
    {
        return $this->hasMany(GroupTrainTableSelection::class);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function getDashboardUrl()
    {
        return route('group.dashboard', $this);
    }

    public function getAnnouncementsUrl()
    {
        return route('group.announcements', $this);
    }
} 