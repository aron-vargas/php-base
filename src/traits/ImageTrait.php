<?php
namespace Freedom\Traits;

use PDO;
use Freedom\Models\Image;

/**
 *
CREATE TABLE `user_profile_image_join` (
  `user_id` bigint unsigned NOT NULL
	REFERENCES user (user_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  `image_id` bigint NOT NULL
	REFERENCES images (image_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  PRIMARY KEY (`user_id`,`image_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 */

trait ImageTrait
{
    public function AddProfileImage($user_id, $image_id)
    {
        $sth = $this->dbh->prepare("INSERT INTO user_profile_image_join
        (user_id, image_id) VALUES (?,?)");
        $sth->bindParam(1, $user_id, PDO::PARAM_INT);
        $sth->bindParam(2, $image_id, PDO::PARAM_INT);
        $sth->execute();

        $this->images[$image_id] = new Image($image_id);
    }

    public function SetImage($id, $image)
    {
        $this->images[$id] = $image;
    }

    protected function LoadProfileImages(bool $reload = false)
    {
        if ($reload || empty($this->images))
        {
            $this->images = array();

            $sth = $this->dbh->prepare('SELECT
                image_id
            FROM user_profile_image_join j
            WHERE j.user_id = ?');
            $sth->bindParam(1, $this->user_id, PDO::PARAM_INT);
            $sth->execute();
            while ($data = $sth->fetch(PDO::FETCH_OBJ))
            {
                $this->images[$data->image_id] = new Image($data->image_id);
            }
        }
    }

    public function HasImage(int $image_id)
    {
        if (!empty($this->images))
        {
            foreach ($this->images as $image)
            {
                if ($image->image_id == $image_id)
                    return true;
            }
        }

        return false;
    }

    public function RMProfileImages()
    {
        $sth = $this->dbh->prepare('DELETE FROM user_profile_image_join j WHERE j.user_id = ?');
        $sth->execute(array($this->user_id));
        $this->images = array();
    }
}