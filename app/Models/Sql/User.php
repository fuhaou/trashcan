<?php
/**
 * Warning: This class is generated automatically by schema_update
 *          !!! Do not touch or modify !!!
 */

namespace App\Models\Sql;

use Illuminate\Database\Eloquent\Model;
#---- Begin package usage -----#

#---- Ended package usage -----#

class User extends Model
{
    #---- Begin trait -----#
    
    #---- Ended trait -----#

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

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
    const COL_USER_ID = 'user_id';

    /**
     * @var string
     */
    const COL_USER_NAME = 'user_name';

    /**
     * @var string
     */
    const COL_USER_PASSWORD = 'user_password';

    /**
     * @var string
     */
    const COL_USER_EMAIL = 'user_email';

    /**
     * @var string
     */
    const COL_USER_PHONE = 'user_phone';

    /**
     * @var string
     */
    const COL_USER_ADDRESS = 'user_address';

    /**
     * @var string
     */
    const COL_USER_ADMIN = 'user_admin';

    /**
     * @var string
     */
    const COL_USER_CREATED_AT = 'user_created_at';

    /**
     * @var string
     */
    const COL_USER_UPDATED_AT = 'user_updated_at';

    

    /**
     * @const string
     */
    const TABLE_NAME = 'user';

    #---- Begin custom code -----#
    
    #---- Ended custom code -----#
}