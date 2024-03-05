
<div class='container'>
    <div class='border'>
        <div class='mb-4'>
            <label class="" for="">Table Name</label>
            <input id="table_name" type='text' class="form-control"/>
        </div>
        <div class='mb-4'>
            <label class="" for="">Class Name</label>
            <input id="class_name" type='text' class="form-control"/>
        </div>
        <div class="row">
            <div class="col">
                <div class='mb-4'>
                    <label class="" for="">Field Name</label>
                    <input id="field_name" type='text' class="form-control"/>
                </div>
                <div class='mb-4'>
                    <label class="" for="">PDO Type</label>
                    <input id="pdo_type" type='text' class="form-control"/>
                </div>
                <div class='mb-4'>
                    <label class="" for="">DB Type</label>
                    <input id="db_type" type='text' class="form-control"/>
                </div>
                <div class='mb-4'>
                    <label class="" for="">Nullable</label>
                    <input id="nullable" type='checkbox' class="form-control"/>
                </div>
                <div class='mb-4'>
                    <label class="" for="">Length</label>
                    <input id="length" type='text' class="form-control"/>
                </div>
            </div>
        </div>
    </div>
    <div class ="row">
        <div class='col'>
            <h3>Class</h3>
            <div class='border'>
                <code id="class-code" contenteditable="true">
&lt;?php
namespace \Freedom\Models

use PDO;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;

class {{ClassName}} extends CDModel
{
    public $pkey;                # integer
    public $key_name = "pkey";   # string
    protected $db_table = "{{table-name}}";   # string
    {{ClassFields}}

    /**
     * Create a new instance
     * @param integer $pkey
     */
    public function __construct($pkey = null)
    {
        $this->pkey = $pkey;
        $this->{$this->key_name} = $pkey;
        $this->dbh = DBSettings::DBConnection();
        $this->SetFieldArray();
        $this->Load();
    }

    private function SetFieldArray()
    {
        $i = 0;
        {{DBFileds}}
    }
}
                </code>
            </div>
        </div>
        <div class='col'>
            <h3>FieldArray</h3>
            <div class='border'>
                <code id='field_array' contenteditable="true">
$this->field_array[$i++] = new DBField('created_at', PDO::PARAM_STR, false, 0);
$this->field_array[$i++] = new DBField('updated_at', PDO::PARAM_STR, false, 0);
                </code>
            </div>
        </div>
        <div class='col'>
            <h3>DB SQL</h3>
            <div class='border'>
                <code contenteditable="true">
CREATE TABLE `{{table-name}}` (
  `pkey` int unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
                </code>
            </div>
        </div>
    </div>

                CREATE TABLE `user` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `nick_name` varchar(255) DEFAULT 'Sucker',
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `user_type` varchar(255) NOT NULL DEFAULT 'SYSTEM',
  `status` varchar(255) NOT NULL DEFAULT 'NEW',
  `verification` varchar(255) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT '0',
  `login_attempts` int DEFAULT '0',
  `block_expires` datetime DEFAULT NULL,
  `created_on` datetime NOT NULL,
  `last_mod` datetime NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
</div>
</div>
</div>
</div>
<script type='text/javascript'>
    $(function ()
    {
        $('.table').DataTable({
            paging: false,
            info: false,
            order: [[0, 'asc']]
        });
    });
</script>