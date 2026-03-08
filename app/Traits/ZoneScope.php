<?php

namespace App\Traits;

use App\Models\User;

/**
 * Fournit des méthodes helper pour filtrer les données selon la zone
 * géographique de l'utilisateur connecté. Admin voit tout, les autres
 * voient uniquement les données de leur zone et descendants.
 */
trait ZoneScope
{
    /**
     * Retourne les IDs de zones accessibles par l'utilisateur.
     * null = pas de filtre (admin).
     */
    protected function getZoneIds(User $user): ?array
    {
        return $user->getAccessibleAreaIds();
    }

    /**
     * Applique le filtre de zone sur une query de Households.
     */
    protected function applyHouseholdZoneFilter($query, User $user)
    {
        $areaIds = $this->getZoneIds($user);

        if ($areaIds === null) {
            return $query;
        }

        return $query->whereIn('households.geographic_area_id', $areaIds);
    }

    /**
     * Retourne les IDs des ménages accessibles par l'utilisateur.
     */
    protected function getAccessibleHouseholdIds(User $user): ?array
    {
        $areaIds = $this->getZoneIds($user);

        if ($areaIds === null) {
            return null;
        }

        return \App\Models\Household::whereIn('geographic_area_id', $areaIds)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Retourne les IDs des utilisateurs dans la zone de l'utilisateur courant.
     */
    protected function getAccessibleUserIds(User $user): ?array
    {
        $areaIds = $this->getZoneIds($user);

        if ($areaIds === null) {
            return null;
        }

        return User::whereIn('geographic_area_id', $areaIds)
            ->pluck('id')
            ->toArray();
    }
}
