<?php echo "
<?php
/**
 * Warning: This class is generated automatically by schema_update
 *          !!! Do not touch or modify !!!
 */

namespace App\Models\Sql;

use Illuminate\Database\Eloquent\Model;
#---- Begin package usage -----#
{$contentUsed}
#---- Ended package usage -----#

class {$className} extends {$baseClassName}
{
    #---- Begin trait -----#
    {$contentUseTrait}
    #---- Ended trait -----#

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected \$table = '{$tableName}';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected \$primaryKey = '{$primaryKey}';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected \$guarded = [];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public \$incrementing = {$incrementing};

    public \$timestamps = false;

    {$constants}

    /**
     * @const string
     */
    const TABLE_NAME = '{$tableName}';

    #---- Begin custom code -----#
    {$contentDevCode}
    #---- Ended custom code -----#
}";
