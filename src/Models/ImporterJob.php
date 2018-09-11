<?php
namespace RentalManager\Importer\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/11/18
 * Time: 10:23 AM
 */


class ImporterJob extends Model
{
    protected $fillable = [
        'provider_id',
        'parser'
    ];
}
