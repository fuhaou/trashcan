<?php
/**
 * Warning: This class is generated automatically by schema_update
 *          !!! Do not touch or modify !!!
 */

namespace App\Models\Sql;

use Illuminate\Database\Eloquent\Model;
#---- Begin package usage -----#

#---- Ended package usage -----#

class TrashLocation extends Model
{
    #---- Begin trait -----#
    
    #---- Ended trait -----#

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'trash_location';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'trash_location_id';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    public $timestamps = false;

    /**
     * @var string
     */
    const COL_TRASH_LOCATION_ID = 'trash_location_id';

    /**
     * @var string
     */
    const COL_TRASH_LOCATION_NAME = 'trash_location_name';

    /**
     * @var string
     */
    const COL_TRASH_LOCATION_ADDRESS = 'trash_location_address';

    /**
     * @var string
     */
    const COL_TRASH_LOCATION_CREATED_AT = 'trash_location_created_at';

    /**
     * @var string
     */
    const COL_TRASH_LOCATION_UPDATED_AT = 'trash_location_updated_at';

    

    /**
     * @const string
     */
    const TABLE_NAME = 'trash_location';

    #---- Begin custom code -----#
    
    #---- Ended custom code -----#
}