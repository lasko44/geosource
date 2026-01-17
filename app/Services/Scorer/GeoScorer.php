<?php

namespace App\Services\Scorer;

class GeoScorer
{
    public function score(Page $page): array
    {
        $definition = (new DefinitionScorer)->score($page);
        $structure = (new StructureScorer)->score($page);
        $authority = (new AuthorityScorer)->score($page);
        $machine = (new MachineReadableScorer)->score($page);
        $answerability = (new AnswerabilityScorer)->score($page);

        $total =
            $definition['score'] +
            $structure['score'] +
            $authority['score'] +
            $machine['score'] +
            $answerability['score'];

        return [
            'total' => $total,
            'breakdown' => compact(
                'definition',
                'structure',
                'authority',
                'machine',
                'answerability'
            ),
        ];
    }
}
