<?
/**
 * Provides UserInfo class definition
 *
 * @author Aron Vargas
 * @package Freedom
 */
class UserInfo {
    private $dbh = null;

    protected $user_id = null;		// @var int
    protected $birth_month = null;		// @var int 1-12
    protected $birth_day = null;		// @var int 1-31
    protected $hobbies = null;		// @var string
    protected $about = null;		// @var about

    protected $images = array();	// @var images
    protected $imagenames = array();	// @var image names
    protected $default_image = null;	// @var default image value
    protected $news = array();	// @var news
    protected $assets = array();	// @var assets

    private $has_record = false;	// @var boolean

    /*
     * Build instance for the id given
     */
    public function __construct($user_id)
    {
        $this->dbh = DataStor::getHandle();
        $this->user_id = $user_id;
        $this->load();
    }

    /*
     * Set class property matching the array key
     *
     * @param array $new
     */
    public function copyFromArray($new = array())
    {
        foreach ($new as $key => $value)
        {
            if (@property_exists($this, $key))
            {
                # Cant trim an array
                if (is_array($value))
                    $this->{$key} = $value;
                else
                    $this->{$key} = trim($value);
            }
        }
    }

    /*
     * Populates this object from the matching record in the database.
     */
    public function load()
    {
        if ($this->user_id)
        {
            $sth = $this->dbh->prepare("SELECT * FROM user_info	WHERE user_id = {$this->user_id}");
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                foreach ($row as $key => $value)
                {
                    if (@property_exists($this, $key))
                    {
                        $this->{$key} = trim($value);
                    }
                }
                $this->has_record = true;
            }
            $this->LoadImages();
            $this->LoadNews();

            # REMOVED BY ISSUE 2269
            #$this->LoadAssets();
        }
    }

    /*
     * Return image id array for a user
     */
    private function LoadImages()
    {
        $this->images = array();
        $this->imagenames = array();

        $sql = "
SELECT image_id, file_name, default_image
FROM user_image
WHERE user_id = ?
ORDER BY display_order";
        $sth = $this->dbh->prepare($sql);
        $sth->bindValue(1, (int) $this->user_id, PDO::PARAM_INT);
        $sth->execute();

        while (list($image_id, $filename, $default_image) = $sth->fetch(PDO::FETCH_NUM))
        {
            $this->images[] = $image_id;
            $this->imagenames[] = $filename;
            if ($default_image)
                $this->default_image = $image_id;
        }
    }

    /*
     * Return news array for a user
     */
    private function LoadNews()
    {
        $this->news = array();
        $sql = "SELECT n.news_id, n.category_id, c.category_text, n.news_date, n.news_headline, n.news_body
				FROM user_news n
				INNER JOIN news_category c ON n.category_id = c.category_id
				WHERE n.user_id = ?
				ORDER BY n.news_date DESC";
        $sth = $this->dbh->prepare($sql);
        $sth->bindValue(1, (int) $this->user_id, PDO::PARAM_INT);
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $this->news[] = $row;
        }

    }

    /**
        * REMOVED BY ISSUE 2269
        *
        * Populate assets array for a user
        *
       private function LoadAssets()
       {
           $this->assets = array();
           $sql = "SELECT a.primary_function, a.manufacturer, a.model, a.other_hw
                   FROM asset a
                   INNER JOIN users u ON a.user_name = u.firstname || ' ' || u.lastname
                   WHERE u.id = ?
                   ORDER BY a.asset_number DESC";
           $sth = $this->dbh->prepare($sql);
           $sth->bindValue(1, (int)$this->user_id, PDO::PARAM_INT);
           $sth->execute();
           while ($row = $sth->fetch(PDO::FETCH_ASSOC))
           {	$this->assets[] = $row; }
       }
       */

    /*
     * Saves the contents of this object to the database. If this object
     * has an id, the record will be UPDATE'd.  Otherwise, it will be
     * INSERT'ed
     *
     * @param array $new
     */
    public function save($form)
    {
        // Load new values
        if (is_array($form))
            $this->copyFromArray($form);

        if ($this->user_id > 0 && $this->has_record)
        {
            $sth = $this->dbh->prepare("
					UPDATE user_info
						SET
							birth_month = ?,
							birth_day = ?,
							about = ?,
							hobbies = ?
					WHERE user_id = ?");
        }
        else
        {
            $sth = $this->dbh->prepare("INSERT INTO user_info (birth_month,birth_day,about,hobbies,user_id) VALUES (?,?,?,?,?)");
        }
        $sth->bindValue(1, (int) $this->birth_month, PDO::PARAM_INT);
        $sth->bindValue(2, (int) $this->birth_day, PDO::PARAM_INT);
        $sth->bindValue(3, $this->about, PDO::PARAM_STR);
        $sth->bindValue(4, substr($this->hobbies, 0, 255), PDO::PARAM_STR);
        $sth->bindValue(5, (int) $this->user_id, PDO::PARAM_INT);
        $sth->execute();
    }

    /*
     * Upload image file into DB
     *
     * @param form associative array
     */
    public function SaveImage($form)
    {
        if (isset ($_FILES['img_file']) && is_uploaded_file($_FILES['img_file']['tmp_name']))
        {
            $filename = $_FILES['img_file']['name'];
            $path_info = pathinfo($filename);

            if (!isset ($path_info['filename']))
                $path_info['filename'] = basename($filename, '.' . $path_info['extension']);

            if ($_FILES['img_file']['error'] == 0)
            {
                $stripped_filename = preg_replace("/[^A-Za-z0-9]/", "", $path_info['filename']);
                $filename = "{$stripped_filename}.{$path_info['extension']}";

                if (is_file("/var/www/html" . Config::$WEB_PATH . "/images/user_image/{$filename}"))
                {
                    $i = 2;
                    while (is_file("/var/www/html" . Config::$WEB_PATH . "/images/user_image/{$path_info['filename']}-{$i}.{$path_info['extension']}"))
                        ++$i;

                    $filename = "{$path_info['filename']}-{$i}.{$path_info['extension']}";
                }

                $default_image = (!isset ($this->default_image)) ? true : false;

                $mimetype = $_FILES['img_file']['type'];
                $display_order = isset ($form['display_order']) ? $form['display_order'] : 1;
                copy($_FILES['img_file']['tmp_name'], "/var/www/html" . Config::$WEB_PATH . "/images/user_image/{$filename}");

                $sql = "INSERT INTO user_image (user_id, file_name, mimetype, display_order, default_image) VALUES (?,?,?,?,?)";
                $sth = $this->dbh->prepare($sql);
                $sth->bindValue(1, (int) $this->user_id, PDO::PARAM_INT);
                $sth->bindValue(2, $filename, PDO::PARAM_STR);
                $sth->bindValue(3, $mimetype, PDO::PARAM_STR);
                $sth->bindValue(4, (int) $display_order, PDO::PARAM_INT);
                $sth->bindValue(5, (int) $default_image, PDO::PARAM_INT);
                $this->dbh->beginTransaction();
                $sth->execute();
                $this->dbh->commit();

                $this->LoadImages();
            }
        }
    }

    /*
     * Delete an image from the DB
     *
     * @param form associative array
     */
    public function RemoveImage($form)
    {
        if (isset ($form['img_id']))
        {
            $sql = "DELETE FROM user_image WHERE image_id = ? and user_id = ?";
            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(1, (int) $form['img_id'], PDO::PARAM_INT);
            $sth->bindValue(2, (int) $this->user_id, PDO::PARAM_INT);
            $sth->execute();

            $this->LoadImages();
        }
    }

    /*
     * Save news to DB
     *
     * @param form associative array
     */
    public function SaveNews(&$form)
    {
        global $user;

        $category_id = (isset ($form['category_id'])) ? $form['category_id'] : 1;
        $news_headline = (isset ($form['news_headline'])) ? substr($form['news_headline'], 0, 128) : "";
        $news_body = (isset ($form['news_body'])) ? $form['news_body'] : "";

        if (isset ($form['news_id']) && $form['news_id'] > 0)
        {
            $sth = $this->dbh->prepare("UPDATE user_news
			SET
				news_date = ?,
				category_id = ?,
				news_headline = ?,
				news_body = ?
			WHERE user_id = ?
				AND news_id = ?");
            $sth->bindValue(6, (int) $form['news_id'], PDO::PARAM_INT);
        }
        else
        {
            $sth = $this->dbh->prepare("INSERT INTO user_news (news_date, category_id, news_headline, news_body, user_id) VALUES (?,?,?,?,?)");
        }
        $sth->bindValue(1, time(), PDO::PARAM_INT);
        $sth->bindValue(2, (int) $category_id, PDO::PARAM_INT);
        $sth->bindValue(3, $news_headline, PDO::PARAM_STR);
        $sth->bindValue(4, $news_body, PDO::PARAM_STR);
        $sth->bindValue(5, (int) $user->getId(), PDO::PARAM_INT);
        $sth->execute();

        if (!isset ($form['news_id']) || $form['news_id'] == 0)
            $form['news_id'] = $this->dbh->lastInsertId("user_news_news_id_seq");

        $this->LoadNews();
    }

    /*
     * Delete news record
     *
     * @param form associative array
     */
    public function RemoveNews($form)
    {
        global $user;

        if (isset ($form['news_id']))
        {
            $sql = "DELETE FROM user_news WHERE news_id = ? AND user_id = ?";
            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(1, (int) $form['news_id'], PDO::PARAM_INT);
            $sth->bindValue(2, (int) $this->user_id, PDO::PARAM_INT);
            $sth->execute();
        }
    }

    /*
     * Returns the class Property value defined by $var.
     *
     * @return mixed
     */
    public function getVar($var = null)
    {
        $ret = null;
        if (@property_exists($this, $var))
        {
            $ret = $this->{$var};
        }
        return $ret;
    }

    /*
     * Returns the class Property value defined by $var.
     *
     * @return mixed
     */
    public function getHTMLVar($var = null)
    {
        $ret = null;
        if (@property_exists($this, $var))
        {
            $ret = htmlentities($this->{$var}, ENT_QUOTES);
        }
        return $ret;
    }

    /*
     * Return profile edit form
     *
     * @param form associative array default input values
     */
    public function ShowEditProfile($form)
    {
        global $user;

        if (is_null($this->hobbies))
            $this->hobbies = "I have not yet entered my hobbies.";
        if (is_null($this->about))
            $this->about = "I have not yet entered anything about myself.";

        $select_month = ($this->birth_month > 0) ? strtotime("{$this->birth_month}/1/2000") : null;
        $month_options = Forms::createMonthList($select_month);

        $day_options = "";
        for ($i = 1; $i <= 31; $i++)
        {
            $sel = ($i == $this->birth_day) ? "selected" : "";
            $day_options .= "<option value='{$i}' {$sel}>{$i}</option>\n";
        }

        $profile = "
	<div align='left'><b>
		<a href='{$_SERVER['PHP_SELF']}'>Employee List</a> ::
		<a href='{$_SERVER['PHP_SELF']}?submit_action=view&user_id={$user->getId()}'>My Profile</a></b>
	</div>
	<form action='{$_SERVER['PHP_SELF']}' method='post'>
	<input type='hidden' name='user_id' value='{$this->user_id}'/>
	<table class='table table-bordered'>
		<tr>
			<th colspan='2' class='subheader'>
				{$user->getName()}
			</th>
		</tr>
		<tr>
			<th class='form'>Birth Day:</th>
			<td class='form'>
				<select name='birth_month'>
					<option value=''>--Select Month--</option>
					{$month_options}
				</select>

				<select name='birth_day'>
					<option value=''>--Select Day--</option>
					{$day_options}
				</select>
			</td>
		</tr>
		<tr>
			<th class='form'>Hobbies:</th>
			<td class='form'>
				<input type='text' name='hobbies' value='{$this->getHTMLVar('hobbies')}' size='50' />
			</td>
		</tr>
		<tr>
			<th class='form'>About Me:</th>
			<td class='form'>
				<textarea name='about' cols='50' rows='3'>{$this->getHTMLVar('about')}</textarea>
			</td>
		</tr>
		<tr>
			<td class='buttons' colspan='2'>
				<input type='submit' name='submit_action' value='Save' />
			</td>
		</tr>
	</table>
	</form>";

        return $profile;
    }

    /*
     * Return contact information section of the user profile.
     *
     * @param show_user User object
     */
    public function GetContactInfo($show_user)
    {
        global $user;
        $info = "";

        $email = $show_user->getEmail();
        if ($email)
            $email = "<a href='mailto:{$email}'>{$email}</a>";

        $address = htmlentities($show_user->getAddress());
        if ($show_user->getAddress2())
            $address .= ", " . htmlentities($show_user->getAddress2());

        # PTO and hire date kept private from others
        $private_row = "";
        if ($show_user->getId() == $user->getId())
        {
            $hire_date = ($user->getHireDate()) ? $user->getHireDate() : "Unknown";

            $private_row = "
			<tr>
				<th class='subsubheader' width='20%'>Hire Date:</th>
				<td class='form' colspan='3'>{$hire_date}</td>
			</tr>";
        }

        return "
		<table class='table table-bordered'>
			<tr>
				<th class='subheader' colspan='4'>Employee Information</th>
			</tr>
			<tr>
				<th class='subsubheader' width='25%'>Title:</th>
				<td class='form' colspan='3'>{$show_user->getTitle()}</td>
			</tr>

				<th class='subsubheader' width='25%'>Credentials:</th>
				<td class='form' colspan='3'>{$show_user->getCredentials()}</td>
			</tr>
			<tr>
				<th class='subsubheader' width='25%'>Phone:</th>
				<td class='form'>{$show_user->getPhone()}</td>
				<th class='subsubheader' width='10%'>Ext:</th>
				<td class='form' width='30%'>{$show_user->getExt()}</td>
			</tr>
			<tr>
				<th class='subsubheader' width='25%'>E-mail:</th>
				<td class='form' colspan='3'>{$email}</td>
			</tr>
			<tr>
				<th class='subsubheader' width='25%'>Address:</th>
				<td class='form' colspan='3'>{$address}</td>
			</tr>
			<tr>
				<th class='subsubheader' width='25%'>City:</th>
				<td class='form'>{$show_user->getCity()}</td>
				<th class='subsubheader' width='10%'>State:</th>
				<td class='form' width='30%'>{$show_user->getState()} {$show_user->getZip()}</td>
			</tr>
			{$private_row}
		</table>";
    }

    /*
     * Return about section of the user profile
     */
    public function GetAboutTable()
    {
        global $user;

        $bm = $this->birth_month;
        $bd = $this->birth_day;
        $hobbies = ($this->hobbies) ? $this->getHTMLVar('hobbies') : "I have not yet entered my hobbies.";
        $about = ($this->about) ? $this->getHTMLVar('about') : "I have not yet entered anything about myself.";
        $sign = "Unknown";

        $edit_button = "";
        if ($this->user_id == $user->getId())
            $edit_button = "<div style='float:right; font-size:small;'><input type='button' class='submit' name='edit' value='Edit' onClick='window.location=\"{$_SERVER['PHP_SELF']}?submit_action=edit\";' /></div>";

        $birth_date = 'Unknown';
        if ($bm > 0 && $bd > 0)
        {
            $birth_date = date("F", strtotime("{$bm}/1/2007")) . ", {$bd}";
            switch ($bd)
            {
                case 1:
                case 21:
                case 31:
                    $birth_date .= "st";
                    break;
                case 2:
                case 22:
                    $birth_date .= "nd";
                    break;
                case 3:
                case 23:
                    $birth_date .= "rd";
                    break;
                default:
                    $birth_date .= "th";
                    break;
            }
        }

        return "
		<table class='table table-bordered'>
			<tr>
				<th class='subheader' colspan='2'>
					<div style='float:left'>Additional Information</div>
					{$edit_button}
				</th>
			</tr>
			<tr>
				<th class='form'>Birth&nbsp;Date</th>
				<td class='form'>{$birth_date}</td>
			</tr>
			<tr>
				<th class='form' colspan='2'>
					Hobbies:<br/>
					<span style='font-size: 8pt; font-weight:normal;'>{$hobbies}</span>
				</th>
			</tr>
			<tr>
				<th class='form' colspan='2'>
					About Me:<br/>
					<span style='font-size: 8pt; font-weight:normal;'>{$about}</span>
				</th>
			</tr>
		</table>";
    }

    /*
     * Return images section of the user profile
     */
    public function GetImageTable()
    {
        global $user;

        $edit_button = "";

        if (isset ($this->images[0]))
        {
            $one = isset ($this->images[1]) ? "<a href='#' onClick=\"
					document.getElementById('img0').style.display='none';
					document.getElementById('img1').style.display='';
					document.getElementById('img2').style.display='none';\">[2]</a>" : "[2] no image";
            $two = isset ($this->images[2]) ? "<a href='#' onClick=\"
					document.getElementById('img0').style.display='none';
					document.getElementById('img1').style.display='none';
					document.getElementById('img2').style.display='';\">[3]</a>" : "[3] no image";
            $img_one = isset ($this->images[1]) ? "<img id='img1' src=\"" . Config::$WEB_PATH . "/images/user_image/{$this->imagenames[1]}\" name='img_frame' height='400' border='0' style='display:none;'/>" : "<span id='img1' style='display:none;'/>[2] no image</span>";
            $img_two = isset ($this->images[2]) ? "<img id='img2' src=\"" . Config::$WEB_PATH . "/images/user_image/{$this->imagenames[2]}\" name='img_frame' height='400' border='0' style='display:none;'/>" : "<span id='img2' style='display:none;'/>3] no image</span>";

            $image = "
		<tr>
			<td align='left' width='33%'>
				<a href='#' onClick=\"
					document.getElementById('img0').style.display='';
					document.getElementById('img1').style.display='none';
					document.getElementById('img2').style.display='none';\">[1]</a>

			</td>
			<td align='center' width='33%'>
				$one
			</td>
			<td align='right' width='33%'>
				$two
			</td>
		<tr>
			<td colspan='3' height='400'>
				<img id='img0' src=\"" . Config::$WEB_PATH . "/images/user_image/{$this->imagenames[0]}\" name='img_frame' height='400' border='0'></img>
				$img_one
				$img_two
			</td>
		</tr>";

            if ($user->getId() == $this->user_id)
                $edit_button = "<div style='float:right;'>
				<input type='button' class='submit' name='edit' value='Edit' onClick='window.location=\"{$_SERVER['PHP_SELF']}?submit_action=edit_image\";' />
			</div>";
        }
        else
        {
            $image = "<tr style='background-color: #f7f7ff; text-align:center; font-size:small;'><td colspan='3'>No Images</td></tr>";

            if ($user->getId() == $this->user_id)
                $edit_button = "<div style='float:right;'>
				<input type='button' class='submit' name='edit' value='Add' onClick='window.location=\"{$_SERVER['PHP_SELF']}?submit_action=edit_image\";' />
			</div>";
        }

        return "
	<table class='table table-bordered'>
		<tr>
			<th colspan='3' class='subheader'>
				<div style='float:left;'>Images</div>
				{$edit_button}
			</th>
		</tr>
		{$image}
	</table>";
    }

    /*
     * Return profile edit form
     *
     * @param form associative array default input values
     */
    public function ShowImgForm($form)
    {
        global $user;
        $this->LoadImages();

        $count = count($this->images);

        $saved_images = "";
        for ($i = 0; $i < $count; $i++)
        {
            $checked = ($this->images[$i] == $this->default_image) ? "checked" : "";

            $saved_images .= "
		<tr>
			<td class='form' align='center'>
				<img src='" . Config::$WEB_PATH . "/images/user_image/{$this->imagenames[$i]}' height='40'/>
			</td>
			<td class='form' align='center'>
				<a href='{$_SERVER['PHP_SELF']}?submit_action=rm_img&img_id={$this->images[$i]}''>Remove</a>
			</td>
			<td class='form' align='center'>
				<input type='radio' name='default_image' id='{$user->getId()}' value='{$this->images[$i]}' {$checked}>
			</td>
		</tr>";
        }

        $form_button = "";

        if ($saved_images != "")
            $form_button = "
		<tr>
			<td colspan='3' class='form' style='text-align:right;'>
				<input type='button' name='set_default_image' value='Set Default Image' onClick='setDefaultImage( \"default_image\" );' />
			</td>
		</tr>";

        $upload_image = "";
        if ($count < 3)
        {
            $count++; // Set display order to count + 1
            $upload_image = "
		<tr>
			<th class='form'>Upload Image:</th>
			<td class='form' colspan='2'>
				<form action='{$_SERVER['PHP_SELF']}' method='post'  enctype='multipart/form-data' onSubmit=\"return LimitImageFile(this);\">
				<input type='hidden' name='display_order' value='{$count}'/>
				<input type='file' name='img_file' size='20' />
				<input type='submit' name='submit_action' value='Upload' />
				</form>
			</td>
		</tr>";
        }

        return "
	<div align='left'><b>
		<a href='{$_SERVER['PHP_SELF']}'>Employee List</a> ::
		<a href='{$_SERVER['PHP_SELF']}?submit_action=view&user_id={$user->getId()}'>My Profile</a></b>
	</div>
	<table class='table table-bordered'>
		<tr>
			<th colspan='3' class='subheader'>Saved Images</th>
		</tr>
		{$saved_images}
		{$form_button}
		{$upload_image}
	</table>";
    }

    /*
     * Set the default image for the users profile.
     */
    public function SetDefaultImage($form)
    {
        global $user;

        $default_image = $form['image_id'];

        $sql = "
UPDATE user_image
SET default_image = CASE
                    WHEN image_id = {$default_image}
                    THEN TRUE
                    ELSE FALSE
                    END
WHERE user_id = {$user->getId()}";

        try
        {
            $this->dbh->exec($sql);
        }
        catch (PDOException $pdo_exc)
        {
            return "A database error has occurred while trying set the default image.\n{$pdo_exc->getMessage()}";
        }

        return "OK";
    }

    /*
     * Returns news section of the user profile
     */
    public function GetNewsTable($news_page = 1)
    {
        global $user, $preferences;

        $date_format = $preferences->get('general', 'dateformat');

        # Determine paging variables
        #
        $results_per_page = $preferences->get('start', 'num_items');
        ;
        $total_records = count($this->news);
        $total_pages = floor($total_records / $results_per_page);
        if ($total_records % $results_per_page)
            $total_pages++;
        # Make sure the page number is between 1 and total
        $news_page = ($news_page > 0) ? $news_page : 1;
        if ($news_page > $total_pages)
            $news_page = $total_pages;
        $record_offset = ($news_page - 1) * $results_per_page;

        # Add the news item rows
        #
        $headlines = ($total_records > 0) ? "" : "<tr class='on'><td>No news to report</td></tr>";
        $row_class = 'on';
        $count = 0;
        foreach ($this->news as $news)
        {
            $count++;
            # Skip to the offset
            if ($count <= $record_offset)
            {
                continue;
            }
            else if ($count <= $record_offset + $results_per_page)
            {
                $news_date = date($date_format, $news['news_date']);
                $news_hl = ($news['news_headline']) ? $news['news_headline'] : "[ edit ]";
                $news_body = nl2br($news['news_body']); //htmlentities($news['news_body'], ENT_QUOTES);

                if ($user->getId() == $this->user_id)
                    $news_hl = "<a href='{$_SERVER['PHP_SELF']}?news_id={$news['news_id']}&submit_action=edit_news&news_page={$news_page}'>{$news_hl}</a>";

                $headlines .= "
			<tr class='{$row_class}'>
				<td align='left'>
				<div style='font-size: 10pt; text-decoration:underline; font-weight:bold;'>{$news_hl}</div>
				<div style='font-size: 9pt; font-weight:bold'><span style='color:#808080;'>{$news['category_text']} :</span> {$news_date}</div>
			    <p style='font-size: 8pt;'>{$news_body}</p>
				</td>
			</tr>";
                $row_class = ($row_class == 'on') ? 'off' : 'on';
            }
        }

        # Create page navigation 5 items per page
        #
        $pages = "<tr><td class=\"list_nav\">";

        # There are previous pages
        if ($news_page > 1)
        {
            $last_page = $news_page - 1;
            # Add the "last page" link
            $pages .= "[<a href='{$_SERVER['PHP_SELF']}?submit_action=view&user_id={$this->user_id}&news_page={$last_page}'>&laquo; Previous {$results_per_page} Items</a>] ";
        }

        # There are additional pages
        if ($news_page < $total_pages)
        {
            $next_page = $news_page + 1;
            # Normally show $results_per_page items on a page
            $n_itmes = $results_per_page;
            # For the last page we will have N itmes <= $results_per_page
            if ($news_page == $total_pages - 1)
                $n_itmes = $total_records - $news_page * $results_per_page;
            # Grammer check
            $ITEMS = ($n_itmes == 1) ? "Item" : "{$n_itmes} Items";

            # Add the "next page" link
            $pages .= " [<a href='{$_SERVER['PHP_SELF']}?submit_action=view&user_id={$this->user_id}&news_page={$next_page}'>Next {$ITEMS} &raquo;</a>]";
        }
        $pages .= " </td></tr>";

        # Add new button when viewing users own profile
        #
        $new_button = "";
        if ($user->getId() == $this->user_id)
            $new_button = "<div style='float:right'>
			<form action='{$_SERVER['PHP_SELF']}' method='post'>
				<input type='submit' class='submit' name='submit_action' value='Add News'/>
				<input type='hidden' name='news_page' value='{$news_page}'/>
			</form>
			</div>";

        # Return the table
        #
        return "
	<table class='table table-bordered'>
		<tr>
			<th class='subheader'>
				<div style='float:left'>News</div>
				{$new_button}
			</th>
		</tr>
		{$headlines}
		{$pages}
	</table>";
    }

    /*
     * Return html form for editing news items
     *
     * @param form associative array
     */
    public function ShowNewsForm($form)
    {
        global $user;

        $delete_button = "";
        $news_page = (isset ($form['news_page'])) ? $form['news_page'] : 1;
        $news_id = (isset ($form['news_id'])) ? $form['news_id'] : 0;
        if ($news_id > 0)
        {
            foreach ($this->news as $news)
            {
                if ($news['news_id'] == $news_id)
                {
                    $form = $news;
                    break;
                }
            }

            $delete_button = "<input type='submit' class='submit' name='submit_action' value='Remove News'/>";
        }
        $category_id = (isset ($form['category_id'])) ? $form['category_id'] : null;
        $news_headline = (isset ($form['news_headline'])) ? htmlentities($form['news_headline'], ENT_QUOTES) : null;
        $news_body = (isset ($form['news_body'])) ? htmlentities($form['news_body'], ENT_QUOTES) : null;

        return "
		<div align='left'><b>
			<a href='{$_SERVER['PHP_SELF']}'>Employee List</a> ::
			<a href='{$_SERVER['PHP_SELF']}?submit_action=view&user_id={$user->getId()}'>My Profile</a></b>
		</div>
		<form action='{$_SERVER['PHP_SELF']}' method='post'>
		<input type='hidden' name='news_id' value='{$news_id}'/>
		<input type='hidden' name='news_page' value='{$news_page}'/>
		<table class='table table-bordered'>
			<tr>
				<th colspan='2' class='subheader'>News</th>
			</tr>
			<tr>
				<th class='form'>Category:</th>
				<td class='form'>
					<select name='category_id'>
					" . self::GetNewsCategoryList($category_id) . "
					</select>
				</td>
			</tr>
			<tr>
				<th class='form'>Headline:</th>
				<td class='form'>
					<input type='text' name='news_headline' value='{$news_headline}' size='50'/>
				</td>
			</tr>
			<tr>
				<th class='form'>Body:</th>
				<td class='form'>
					<textarea name='news_body' cols='60' rows='4'>{$news_body}</textarea>
				</td>
			</tr>
			<tr>
				<td class='buttons' colspan='2'>
					<input type='submit' class='submit' name='submit_action' value='Save News'/>
					{$delete_button}
				</td>
			</tr>
		</table>
		</form>
		";
    }

    /**
        * REMOVED BY ISSUE 2269
        *
        * Return the users assets as an html table
        *
       public function GetAssetTable()
       {
           # Create a table row for each asset
           $assets = "<tr class='on'><td colspan='3'>No Assets Found</td></tr>";
           if (is_array($this->assets) && count($this->assets) > 0)
           {
               # Remove default row
               $assets = "";
               $row_class = "on";
               foreach ($this->assets AS $asset)
               {
                   $assets .= "
                   <tr class='$row_class'>
                       <td align='left'>{$asset['primary_function']}</td>
                       <td align='left'>{$asset['manufacturer']}: {$asset['model']}</td>
                       <td align='left'>{$asset['other_hw']}</td>
                   </tr>";
                   $row_class = ($row_class == 'on') ? 'off' : 'on';
               }
           }

           # Return the table
           #
           return "
       <table width='100%' class='list' cellspacing='2' cellpadding='4' style='margin: 0 0 5 0;'>
           <tr>
               <th class='subheader' colspan='3'>IT Assets</th>
           </tr>
           <tr>
               <th class='list'>Device</th>
               <th class='list'>Model</th>
               <th class='list'>Additional HW</th>
           </tr>
           {$assets}
       </table>";
       }
       */

    /*
     * Build a list of options based on DB table
     *
     * @param match category_id of default selected option
     *
     * @return sting html appropriate for an select input
     */
    static public function GetNewsCategoryList($match)
    {
        $dbh = DataStor::getHandle();

        $options = '';
        $sth = $dbh->prepare("SELECT category_id, category_text
		FROM news_category
		ORDER BY display_order");
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $sel = ($row['category_id'] == $match) ? 'selected' : '';
            $options .= "<option value=\"{$row['category_id']}\" $sel>{$row['category_text']}</option>";
        }

        return $options;
    }
}
?>