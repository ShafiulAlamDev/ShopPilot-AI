<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductEmbedding extends Model
{
    protected $fillable = [
        'product_id', 'embedding', 'embedding_model', 'embedded_text',
    ];

    // Do NOT cast embedding to array here — we decode manually for performance

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Decode the stored JSON embedding to a float array.
     *
     * @return float[]
     */
    public function getVector(): array
    {
        return json_decode($this->embedding, true);
    }
}
