<?php

namespace App\Queries\Jobs;

use App\Models\JobApply;
use Illuminate\Support\LazyCollection;

final class JobApplicationExportQuery
{
    /**
     * @return LazyCollection<int, JobApply>
     */
    public function forOwner(int $ownerId): LazyCollection
    {
        return JobApply::query()
            ->where('owner_id', $ownerId)
            ->orderBy('id')
            ->lazyById(500);
    }
}
