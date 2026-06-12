<?php

namespace App\Traits;

trait TracksDeletionImpact
{
    /**
     * Get the count of related records for this model instance.
     */
    public function getDeletionImpact(): array
    {
        $impacts = [];

        // Define relations that should be checked
        $relations = $this->getImpactRelations();

        foreach ($relations as $relationName => $label) {
            if (method_exists($this, $relationName)) {
                $count = $this->{$relationName}()->count();
                if ($count > 0) {
                    $impacts[] = [
                        'label' => $label,
                        'count' => $count,
                    ];
                }
            }
        }

        return $impacts;
    }

    /**
     * List of relations to check for impact. Override in model.
     * Format: ['relationMethod' => 'Human Readable Label']
     */
    protected function getImpactRelations(): array
    {
        return [];
    }
}
