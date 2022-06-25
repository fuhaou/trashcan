<?php
/**
 * Warning: This class is generated automatically by schema_update
 *          !!! Do not touch or modify !!!
 */

namespace App\Models\Sql;

use Illuminate\Database\Eloquent\Model;
#---- Begin package usage -----#

#---- Ended package usage -----#

class TrashGroup extends Model
{
    #---- Begin trait -----#
    
    #---- Ended trait -----#

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'trash_group';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'trash_group_id';

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
    const COL_TRASH_GROUP_ID = 'trash_group_id';

    /**
     * @var string
     */
    const COL_TRASH_GROUP_NAME = 'trash_group_name';

    /**
     * @var string
     */
    const COL_TRASH_GROUP_ADDRESS = 'trash_group_address';

    /**
     * @var string
     */
    const COL_TRASH_LOCATION_INDEX = 'trash_location_index';

    /**
     * @var string
     */
    const COL_TRASH_GROUP_CREATED_AT = 'trash_group_created_at';

    /**
     * @var string
     */
    const COL_TRASH_GROUP_UPDATED_AT = 'trash_group_updated_at';

    

    /**
     * @const string
     */
    const TABLE_NAME = 'trash_group';

    #---- Begin custom code -----#
    
    #---- Ended custom code -----#
}