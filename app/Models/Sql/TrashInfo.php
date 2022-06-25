<?php
/**
 * Warning: This class is generated automatically by schema_update
 *          !!! Do not touch or modify !!!
 */

namespace App\Models\Sql;

use Illuminate\Database\Eloquent\Model;
#---- Begin package usage -----#

#---- Ended package usage -----#

class TrashInfo extends Model
{
    #---- Begin trait -----#
    
    #---- Ended trait -----#

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'trash_info';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'trash_info_id';

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
    const COL_TRASH_INFO_ID = 'trash_info_id';

    /**
     * @var string
     */
    const COL_USER_INDEX = 'user_index';

    /**
     * @var string
     */
    const COL_TRASH_INDEX = 'trash_index';

    /**
     * @var string
     */
    const COL_TRASH_INFO_WEIGHT = 'trash_info_weight';

    /**
     * @var string
     */
    const COL_TRASH_INFO_CREATED_AT = 'trash_info_created_at';

    /**
     * @var string
     */
    const COL_TRASH_INFO_UPDATED_AT = 'trash_info_updated_at';

    

    /**
     * @const string
     */
    const TABLE_NAME = 'trash_info';

    #---- Begin custom code -----#
    
    #---- Ended custom code -----#
}