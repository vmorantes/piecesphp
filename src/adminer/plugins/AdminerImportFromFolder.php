<?php
/**
 * Import databases from a folder
 *
 * @author joshcangit, http://github.com/joshcangit/
 */
class AdminerImportFromFolder {
    private $folder;

    /**
    * $folder - directory containing .sql files
    */
    function __construct($folder = null){
        $this->folder = $folder;
    }

    function importServerPath() {
        ?> <select name="file">
            <option value=""></option>
        <?php
        if ($this->folder) {
            $folder = $this->folder."/";
        } else $folder = $this->folder;
        // load all .sql files
        foreach (glob($folder."*.sql") as $path) {
            $file = preg_replace("~(?:.+\/)?(.+\.sql)~", "$1", $path);
            ?> <option value="<?php echo $path; if ($_POST['file'] == $path) return $_POST['file']; ?>"><?php echo $file; ?></option>
            <?php
        }
        ?>
        </select>
        <?php
    }
}
?>