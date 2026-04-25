<?php

namespace App\Services;

use App\Models\ErpNumberSequence;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ErpNumberSequenceService
{
    public function generate(string $key): string
    {
        return DB::transaction(function () use ($key): string {
            $sequence = ErpNumberSequence::query()
                ->where('erp_number_sequence_key', $key)
                ->lockForUpdate()
                ->first();

            if (! $sequence instanceof ErpNumberSequence) {
                throw new RuntimeException("Sequence [{$key}] not found.");
            }

            $nextNumber = $sequence->erp_number_sequence_current_number + 1;

            $sequence->update([
                'erp_number_sequence_current_number' => $nextNumber,
            ]);

            return $sequence->erp_number_sequence_prefix.str_pad(
                (string) $nextNumber,
                $sequence->erp_number_sequence_padding,
                '0',
                STR_PAD_LEFT
            );
        });
    }
}
