<?php
namespace Freedom\Traits;

use PDO;
use Freedom\Models\Attachment;

/**
 *
CREATE TABLE `blog_attachment_join` (
  `blog_id` bigint unsigned NOT NULL
	REFERENCES blog_post (pkey)
    ON UPDATE CASCADE ON DELETE CASCADE,
  `attachment_id` bigint NOT NULL
	REFERENCES attachment (pkey)
    ON UPDATE CASCADE ON DELETE CASCADE,
  PRIMARY KEY (`user_id`,`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 */

trait AttachmentTrait
{
    public function AddBlogAttachment($blog_id, $attachment_id)
    {
        $sth = $this->dbh->prepare("INSERT INTO blog_attachment_join
        (blog_id, attachment_id) VALUES (?,?)");
        $sth->bindParam(1, $blog_id, PDO::PARAM_INT);
        $sth->bindParam(2, $attachment_id, PDO::PARAM_INT);
        $sth->execute();

        $this->attachments[$attachment_id] = new Attachment($attachment_id);
    }

    public function SetRole($id, $attachment)
    {
        $this->attachments[$id] = $attachment;
    }

    protected function LoadBlogAttachments(bool $reload = false)
    {
        if ($reload || empty($this->attachments))
        {
            $this->attachments = array();

            $sth = $this->dbh->prepare('SELECT
                blog_id
            FROM blog_attachment_join j
            WHERE j.attachment_id = ?');
            $sth->bindParam(1, $this->pkey, PDO::PARAM_INT);
            $sth->execute();
            while ($data = $sth->fetch(PDO::FETCH_OBJ))
            {
                $this->attachments[$data->blog_id] = new Attachment($data->blog_id);
            }
        }
    }

    public function HasRole(int $attachment_id)
    {
        if (!empty($this->attachments))
        {
            foreach ($this->attachments as $attachment)
            {
                if ($attachment->attachment_id == $attachment_id)
                    return true;
            }
        }

        return false;
    }

    public function RMUserRoles()
    {
        $sth = $this->dbh->prepare('DELETE FROM blog_attachment_join j WHERE j.blog_id = ?');
        $sth->execute(array($this->pkey));
        $this->attachments = array();
    }
}