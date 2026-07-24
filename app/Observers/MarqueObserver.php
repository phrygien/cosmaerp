<?php

namespace App\Observers;

use App\Models\Marque;

class MarqueObserver
{
    /**
     * Handle the Marque "created" event.
     */
    public function created(Marque $marque): void
    {
        $marque->products()->searchable();
    }

    /**
     * Handle the Marque "updated" event.
     */
    public function updated(Marque $marque): void
    {
        if ($marque->wasChanged('code')) {
            $marque->products()->searchable();
        }
    }

    /**
     * Handle the Marque "deleted" event.
     */
    public function deleted(Marque $marque): void
    {
        $marque->products()->searchable();
    }

    /**
     * Handle the Marque "restored" event.
     */
    public function restored(Marque $marque): void
    {
        //
    }

    /**
     * Handle the Marque "force deleted" event.
     */
    public function forceDeleted(Marque $marque): void
    {
        //
    }
}
