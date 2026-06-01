<?php

namespace App\Console\Commands;

use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Models\GeoCorrelation;
use App\Models\GeoStudyEntry;
use App\Models\Scan;
use Illuminate\Console\Command;

/**
 * Reassigns all study-related scans, citation queries, and checks to a specific user.
 */
class ReassignStudyData extends Command
{
    protected $signature = 'study:reassign {user_id : The user ID to reassign everything to}';

    protected $description = 'Reassign all study scans, citations, and correlations to a specific user';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');

        $scanIds = GeoStudyEntry::whereNotNull('scan_id')->pluck('scan_id');
        $queryIds = GeoStudyEntry::whereNotNull('citation_query_id')->pluck('citation_query_id');

        $scans = Scan::whereIn('id', $scanIds)->where('user_id', '!=', $userId)->update(['user_id' => $userId]);
        $queries = CitationQuery::whereIn('id', $queryIds)->where('user_id', '!=', $userId)->update(['user_id' => $userId]);
        $checks = CitationCheck::whereIn('citation_query_id', $queryIds)->where('user_id', '!=', $userId)->update(['user_id' => $userId]);
        $correlations = GeoCorrelation::where('user_id', '!=', $userId)->update(['user_id' => $userId]);

        $this->info("Reassigned to user {$userId}: {$scans} scans, {$queries} queries, {$checks} checks, {$correlations} correlations");

        return Command::SUCCESS;
    }
}
