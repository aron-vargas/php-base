<style>
    .elegant-bg {
        background-image: url('/images/elegant-bg.png');
        padding: 20px;
        border-radius: 15px;
    }

    .shadow {
        background-color: rgba(255, 255, 255, 0.2);
        padding: 10px;
        border-radius: 5px;
    }

    .elegant-bg label {
        font-weight: bolder;
        color: white;
    }

    .cf-col {
        background-color: #AEB2D5;
        border-right: 3px solid white;
    }

    .fa-col {
        background-color: #AEB2D5;
        border-right: 3px solid white;
    }

    .sql-col {
        color: white;
        background-color: rgb(0, 117, 143);
    }

    .php-col {
        background-color: #777BB3;
    }

    code,
    pre,
    code pre {
        margin-bottom: 0;
    }

    .cf-col .border,
    .fa-col .border,
    .sql-col .border,
    .php-col .border {
        color: black;
        background-color: white;
        font-size: .8em;
    }

    .container-fluid {
        margin-left: 100px;
        margin-right: 100px;
        width: initial;
    }

    .container-fluid .float-end i.btn {
        color: inherit;
        padding: 4px;
        margin: 4px 0 0 2px;
        border-color: gainsboro;
    }

    .container-fluid .float-end i.btn:hover {
        color: white;
    }
</style>
<div class='container-fluid'>
    <div class='card-body mt-2 mb-4 p-2 border'>
        <div class='elegant-bg'>
            <div class='shadow'>
                <div class="row">
                    <div class="col">
                        <div class='mb-4'>
                            <label class="" for="">Table Name</label>
                            <div class='input-group'>
                                <input id="table_name" type='text' class="form-control" />
                                <button type='button' class='btn btn-primary'
                                    onClick="SetName('#table_name', '#db_code', '{{table-name}}'); SetName('#table_name', '#class-code', '{{table-name}}');"><i
                                        class='fa fa-pen-to-square'></i></button>
                                <button type='button' class='btn btn-primary'
                                    onClick="ResetName('#table_name', '#db_code', '{{table-name}}'); ResetName('#table_name', '#class-code', '{{table-name}}');"><i
                                        class='fa fa-undo'></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class='mb-4'>
                            <label class="" for="">Class Name</label>
                            <div class='input-group'>
                                <input id="class_name" type='text' class="form-control" />
                                <button type='button' class='btn btn-primary'
                                    onClick="SetName('#class_name', '#class-code', '{{ClassName}}');"><i
                                        class='fa fa-pen-to-square'></i></button>
                                <button type='button' class='btn btn-primary'
                                    onClick="ResetName('#class_name', '#class-code', '{{ClassName}}');"><i
                                        class='fa fa-undo'></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-4 align-items-center">
                    <div class="col-2">
                        <div class='mb-4'>
                            <label class="" for="">Field Name</label>
                            <input id="field_name" type='text' class="form-control" />
                        </div>
                    </div>
                    <div class="col-2">
                        <div class='mb-4'>
                            <label class="" for="">DB Type</label>
                            <select id="db_type" class="form-control">
                                <option value='INT'>INT</option>
                                <option value='TINYINT'>TINYINT</option>
                                <option value='MEDIUMINT'>MEDIUMINT</option>
                                <option value='BIGINT'>BIGINT</option>
                                <option value='VARCHAR'>VARCHAR</option>
                                <option value='DATE'>DATE</option>
                                <option value='TIMESTAMP'>TIMESTAMP</option>
                                <option value='DATETIME'>DATETIME (larger)</option>
                                <option value='TEXT'>TEXT</option>
                                <option value='TINYTEXT'>TINYTEXT</option>
                                <option value='MEDIUMTEXT'>MEDIUMTEXT</option>
                                <option value='LONGTEXT'>LONGTEXT</option>
                                <option value='BLOB'>BLOB</option>
                                <option value='TINYBLOB'>TINYBLOB</option>
                                <option value='MEDIUMBLOB'>MEDIUMBLOB</option>
                                <option value='LONGBLOB'>LONGBLOB</option>
                                <select>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class='mb-4'>
                            <label class="" for="">PDO Type</label>
                            <select id="pdo_type" class="form-control">
                                <option value='PDO::PARAM_INT'>PDO::PARAM_INT</option>
                                <option value='PDO::PARAM_STR'>PDO::PARAM_STR</option>
                                <option value='PDO::PARAM_BOOL'>PDO::PARAM_BOOL</option>
                                <select>
                        </div>
                    </div>
                    <div class="col">
                        <div class='mb-4'>
                            <label for="nullable">Nullable</label>
                            <div class='form-check form-switch'>
                                <input id="nullable" class='form-check-input' type='checkbox' value='1' />
                            </div>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class='mb-4'>
                            <label class="" for="">Length</label>
                            <input id="length" type='text' class="form-control" />
                        </div>
                    </div>
                    <div class="col-2">
                        <div class='mb-4'>
                            <label class="" for="">Defualt Value</label>
                            <input id="default_val" type='text' class="form-control" />
                        </div>
                    </div>
                    <div class="col">
                        <div class='mb-4'>
                            <button type='button' class='btn btn-primary' onClick="AddField()">
                                <i class='fa fa-pen-to-square'></i>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<div class='container-fluid sql-col pb-2 mb-2'>
    <span class='float-end'>
        <i class="fa fa-copy btn btn-outline-dark" onClick="Copy('#db_code')"></i>
    </span>
    <h3>DB SQL</h3>
    <div class='border'>
        <pre><code id="db_code" contenteditable="true">
CREATE TABLE `{{table-name}}` (
  `pkey` int unsigned NOT NULL AUTO_INCREMENT,
  --{{DBFields}}
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int NOT NULL
    REFERENCES user (user_id)
    ON UPDATE CASCADE ON DELET CASCADE,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int NOT NULL
    REFERENCES user (user_id)
    ON UPDATE CASCADE ON DELET CASCADE,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci

</code></pre>
    </div>
</div>
<div class='container-fluid'>
    <div class="row mb-4 flex-nowrap">
        <div class='col cf-col'>
            <span class='float-end'>
                <i class='fa fa-pen-to-square btn btn-secondary'
                    onClick="SetCode('#class_fields', '#class-code', '{{ClassFields}}')"></i>
                <i class=" fa fa-copy btn btn-outline-dark" onClick="Copy('#class_fields')"></i>
            </span>
            <h3>Class Field</h3>
            <div class='border'>
                <pre><code id='class_fields' contenteditable="true">
public $created_at;     #', PDO::PARAM_STR, false, 0);
public $created_by;     #` int NOT NULL
public $updated_at;     #', PDO::PARAM_STR, false, 0);
public $updated_by;     #` int NOT NULL

</code></pre>
            </div>
        </div>
        <div class='col fa-col'>
            <span class='float-end'>
                <i class='fa fa-pen-to-square btn btn-secondary'
                    onClick="SetCode('#field_array', '#class-code', '{{FieldArray}}')"></i>
                <i class="fa fa-copy btn btn-outline-dark" onClick="Copy('#field_array')"></i>
            </span>
            <h3>FieldArray</h3>
            <div class='border'>
                <pre><code id='field_array' contenteditable="true">
$this->field_array[$i++] = new DBField('created_at', PDO::PARAM_STR, false, 0);
$this->field_array[$i++] = new DBField('created_by', PDO::PARAM_INT, false, 0);
$this->field_array[$i++] = new DBField('updated_at', PDO::PARAM_STR, false, 0);
$this->field_array[$i++] = new DBField('updated_by', PDO::PARAM_INT, false, 0);

</code></pre>
            </div>
        </div>
    </div>
</div>
<div class='container-fluid'>
    <div class='card-body m-2 p-2 php-col'>
        <span class='float-end'>
            <i class="fa fa-copy btn btn-outline-dark" onClick="Copy('#class-code')"></i>
        </span>
        <h3>Class</h3>
        <div class='border'>
            <pre><code id="class-code" contenteditable="true">
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
    #{{ClassFields}}

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
        #{{FieldArray}}
    }
}

</code></pre>
        </div>
    </div>
</div>
<script type='text/javascript'>
    function AddField()
    {
        var table_name = $('#table_name');
        var class_name = $('#class_name');
        var field_name = $('#field_name');
        var pdo_type = $('#pdo_type');
        var db_type = $('#db_type');
        var nullable = $('#nullable');
        var length = $('#length');
        var default_val = $('#default_val');

        AddClassField(field_name, pdo_type, db_type, nullable, length, default_val);
        AddFieldArray(field_name, pdo_type, db_type, nullable, length, default_val);
        AddDBField(field_name, pdo_type, db_type, nullable, length, default_val);
    }

    function AddClassField(field_name, pdo_type, db_type, nullable, length, default_val)
    {
        var class_fields = $("#class_fields");
        var isNULL = (nullable.prop('checked')) ? "NULL" : "NOT NULL";
        var DefaultVal = (default_val.val()) ? "='" + default_val.val() + "'" : "";
        var DefaultComment = (default_val.val()) ? " DEFAULT '" + default_val.val() + "'" : "";

        var line = "protected $" + field_name.val() + DefaultVal + ";       # " + db_type.val() + " " + isNULL + " " + DefaultComment + "\n";
        class_fields.append(line);
    }
    function AddFieldArray(field_name, pdo_type, db_type, nullable, length, default_val)
    {
        var field_array = $("#field_array");
        var isNULL = (nullable.prop('checked')) ? "true" : "false";
        var len = (length.val()) ? length.val() : 0;
        var line = "$this->field_array[$i++] = new DBField('" + field_name.val() + "', " + pdo_type.val() + ", " + isNULL + ", " + len + ");\n";
        field_array.append(line);
    }
    function AddDBField(field_name, pdo_type, db_type, nullable, length, default_val)
    {
        var db_code = $("#db_code");
        var db_text = db_code.text();
        var location = db_text.indexOf("{{DBFields}}") + 12;
        var begining = db_text.substring(0, location);
        var end = db_text.substring(location);
        var isNULL = (nullable.prop('checked')) ? "NULL" : "NOT NULL";
        var DefaultVal = (default_val.val()) ? " DEFAULT '" + default_val.val() + "'" : "";
        var line = "\n  `" + field_name.val() + "` " + db_type.val() + " " + isNULL + DefaultVal + ",";
        db_code.text(begining + line + end);
    }

    function SetCode(source_code, target_code, tag)
    {
        var src = $(source_code);
        var target = $(target_code);
        var code = target.text();
        var loc = code.indexOf(tag);
        var len = tag.length;
        var begin = code.substring(0, loc + len);
        var end = code.substring(loc + len);
        target.text(begin + src.text() + end);
    }

    function SetName(input, target, tag)
    {
        var new_txt = $(input).val();
        if (new_txt)
        {
            var elem = $(target);
            var elem_txt = elem.text().replace(tag, new_txt);
            elem.text(elem_txt);
        }
        else
            alert("Input text is empty");
    }
    function ResetName(input, target, tag)
    {
        var new_txt = $(input).val();
        if (new_txt)
        {
            var elem = $(target);
            var elem_txt = elem.text().replace(new_txt, tag);
            elem.text(elem_txt);
        }
        else
            alert("Input text is empty");
    }

    function Copy(target)
    {
        var elem = $(target);
        navigator.clipboard.writeText(elem.text());
    }

    $(function ()
    {
        // Nothing Yet
    });
</script>