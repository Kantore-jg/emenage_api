<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class GeographicArea extends Model
{
    protected $fillable = ['name', 'level_id', 'parent_id'];

    public function level()
    {
        return $this->belongsTo(GeographicLevel::class, 'level_id');
    }

    public function parent()
    {
        return $this->belongsTo(GeographicArea::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(GeographicArea::class, 'parent_id');
    }

    public function children_recursive()
    {
        return $this->children()->with('children_recursive');
    }

    public function ancestors(): Collection
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current) {
            $ancestors->prepend($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Retourne tous les IDs descendants (enfants, petits-enfants, etc.)
     * inclus l'ID de la zone elle-même.
     */
    public function getDescendantIds(): array
    {
        $ids = [$this->id];
        $children = self::where('parent_id', $this->id)->pluck('id')->toArray();

        while (!empty($children)) {
            $ids = array_merge($ids, $children);
            $children = self::whereIn('parent_id', $children)->pluck('id')->toArray();
        }

        return $ids;
    }

    /**
     * Retourne le chemin complet: Province > Commune > Zone > Colline
     */
    public function getFullPathAttribute(): string
    {
        $parts = $this->ancestors()->pluck('name')->toArray();
        $parts[] = $this->name;

        return implode(' > ', $parts);
    }

    public function households()
    {
        return $this->hasMany(Household::class, 'geographic_area_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'geographic_area_id');
    }
}
