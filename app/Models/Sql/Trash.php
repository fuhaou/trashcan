<?php
/**
 * Warning: This class is generated automatically by schema_update
 *          !!! Do not touch or modify !!!
 */

namespace App\Models\Sql;

use Illuminate\Database\Eloquent\Model;
#---- Begin package usage -----#

#---- Ended package usage -----#

class Trash extends Model
{
    #---- Begin trait -----#
    
    #---- Ended trait -----#

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'trash';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'trash_id';

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
    const COL_TRASH_ID = 'trash_id';

    /**
     * @var string
     */
    const COL_TRASH_NAME = 'trash_name';

    /**
     * @var string
     */
    const COL_TRASH_QR = 'trash_qr';

    /**
     * @var string
     */
    const COL_TRASH_TYPE_INDEX = 'trash_type_index';

    /**
     * @var string
     */
    const COL_TRASH_LOCATION_INDEX = 'trash_location_index';

    /**
     * @var string
     */
    const COL_TRASH_GROUP_INDEX = 'trash_group_index';

    /**
     * @var string
     */
    const COL_TRASH_CREATED_AT = 'trash_created_at';

    /**
     * @var string
     */
    const COL_TRASH_UPDATED_AT = 'trash_updated_at';

    

    /**
     * @const string
     */
    const TABLE_NAME = 'trash';

    #---- Begin custom code -----#
    
    #---- Ended custom code -----#
}