<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SummarySalesCogs extends Model
{
    protected $table = 'vSummarySalesCogs'; // your SQL Server view
    public $timestamps = false;             // views don't have timestamps
    protected $primaryKey = null;           // no primary key
    public $incrementing = false;           // prevent auto-increment assumption
}
