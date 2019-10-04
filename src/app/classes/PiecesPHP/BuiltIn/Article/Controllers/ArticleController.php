<?php
/**
 * ArticleController.php
 */

namespace PiecesPHP\BuiltIn\Article\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use PiecesPHP\BuiltIn\Article\Category\Controllers\CategoryController;
use PiecesPHP\BuiltIn\Article\Category\Mappers\CategoryMapper;
use PiecesPHP\BuiltIn\Article\Mappers\ArticleMapper as MainMapper;
use PiecesPHP\Core\Forms\FileUpload;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
use PiecesPHP\Core\HTML\HtmlElement;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use Slim\Exception\NotFoundException;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * ArticleController.
 *
 * @package     PiecesPHP\BuiltIn\Article\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class ArticleController extends AdminPanelController
{

    const UPLOAD_DIR = 'general/articles';
    const UPLOAD_DIR_TMP = 'general/articles/tmp';
    const FORMAT_DATETIME = 'd-m-Y h:i A';

    /**
     * $prefixParentEntity
     *
     * @var string
     */
    protected static $prefixParentEntity = 'built-in';
    /**
     * $prefixEntity
     *
     * @var string
     */
    protected static $prefixEntity = 'articles';
    /**
     * $prefixSingularEntity
     *
     * @var string
     */
    protected static $prefixSingularEntity = 'article';
    /**
     * $title
     *
     * @var string
     */
    protected static $title = 'Artículo';
    /**
     * $pluralTitle
     *
     * @var string
     */
    protected static $pluralTitle = 'Artículos';

    /**
     * $uploadDir
     *
     * @var string
     */
    protected $uploadDir = '';
    /**
     * $uploadDir
     *
     * @var string
     */
    protected $uploadTmpDir = '';
    /**
     * $uploadDirURL
     *
     * @var string
     */
    protected $uploadDirURL = '';
    /**
     * $uploadDirTmpURL
     *
     * @var string
     */
    protected $uploadDirTmpURL = '';

    /**
     * __construct
     *
     * @return static
     */
    public function __construct()
    {
        parent::__construct(false); //No cargar ningún modelo automáticamente.

        $this->model = (new MainMapper)->getModel();
        set_title(self::$title);

        $this->uploadDir = append_to_url(get_config('upload_dir'), self::UPLOAD_DIR);
        $this->uploadTmpDir = append_to_url(get_config('upload_dir'), self::UPLOAD_DIR_TMP);
        $this->uploadDirURL = str_replace(base_url(), '', append_to_url(get_config('upload_dir_url'), self::UPLOAD_DIR));
        $this->uploadDirTmpURL = str_replace(base_url(), '', append_to_url(get_config('upload_dir_url'), self::UPLOAD_DIR_TMP));
    }

    /**
     * addForm
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    public function addForm(Request $request, Response $response, array $args)
    {

        set_custom_assets([
            'statics/js/built-in/article/backend/forms.js',
        ], 'js');

        $action = self::routeName('actions-add');
        $back_link = self::routeName('list');
        $quill_proccesor_link = self::routeName('image-handler');
        $options_categories = array_to_html_options(CategoryMapper::allForSelect(), null);

        $data = [];
        $data['action'] = $action;
        $data['back_link'] = $back_link;
        $data['options_categories'] = $options_categories;
        $data['quill_proccesor_link'] = $quill_proccesor_link;
        $data['title'] = self::$title;

        import_quilljs(['imageResize']);
        import_cropper();

        $this->render('panel/layout/header');
        $this->render('panel/' . self::$prefixParentEntity . '/' . self::$prefixSingularEntity . '/add-form', $data);
        $this->render('panel/layout/footer');
    }

    /**
     * editForm
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    public function editForm(Request $request, Response $response, array $args)
    {

        set_custom_assets([
            'statics/js/built-in/article/backend/forms.js',
        ], 'js');

        $id = $request->getAttribute('id', null);
        $id = !is_null($id) && ctype_digit($id) ? (int) $id : null;

        $element = new MainMapper($id);

        if (!is_null($element->id)) {

            $action = self::routeName('actions-edit');
            $back_link = self::routeName('list');
            $quill_proccesor_link = self::routeName('image-handler');
            $options_categories = array_to_html_options(CategoryMapper::allForSelect(), $element->category->id);

            $data = [];
            $data['action'] = $action;
            $data['element'] = $element;
            $data['back_link'] = $back_link;
            $data['options_categories'] = $options_categories;
            $data['quill_proccesor_link'] = $quill_proccesor_link;
            $data['title'] = self::$title;

            import_quilljs(['imageResize']);
            import_cropper();

            $this->render('panel/layout/header');
            $this->render('panel/' . self::$prefixParentEntity . '/' . self::$prefixSingularEntity . '/edit-form', $data);
            $this->render('panel/layout/footer');
        } else {
            throw new NotFoundException($request, $response);
        }
    }

    /**
     * listView
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    public function listView(Request $request, Response $response, array $args)
    {
        set_title(self::$pluralTitle);

        $process_table = self::routeName('datatables');
        $back_link = get_route('admin');
        $add_link = self::routeName('forms-add');

        $data = [];
        $data['process_table'] = $process_table;
        $data['back_link'] = $back_link;
        $data['add_link'] = $add_link;
        $data['has_permissions_add'] = strlen($add_link) > 0;
        $data['title'] = self::$pluralTitle;

        $this->render('panel/layout/header');
        $this->render('panel/' . self::$prefixParentEntity . '/' . self::$prefixSingularEntity . '/list', $data);
        $this->render('panel/layout/footer');
    }

    /**
     * all
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function all(Request $request, Response $response, array $args)
    {

        if ($request->isXhr()) {

            $allDate = $request->getQueryParam('all_date', null);
            $paginate = $request->getQueryParam('paginate', null);

            $page = $request->getQueryParam('page', 1);
            $perPage = $request->getQueryParam('per_page', 5);

            $page = is_integer($page) || ctype_digit($page) ? (int) $page : 1;
            $perPage = is_integer($perPage) || ctype_digit($perPage) ? (int) $perPage : 5;

            $query = $this->model->select()->orderBy('start_date DESC, end_date DESC, created DESC');

            $now = date('Y-m-d H:i:s');

            $where_date_values = [
                "(start_date <= '{$now}' OR start_date IS NULL) AND",
                "(end_date > '{$now}' OR end_date IS NULL)",
            ];
            $where_date_values = implode(' ', $where_date_values);

            if ($allDate !== 'yes') {
                $query->where($where_date_values);
            }

            if ($paginate === 'yes') {

                $response_data = [];

                $response_data['sql'] = $query->getCompiledSQL();
                $query->execute(false, $page, $perPage);
                $data = $query->result();

                $query->resetAll();

                $query->select('COUNT(id) AS total');
                if ($allDate !== 'yes') {
                    $query->where($where_date_values);
                }
                $query->execute();
                $total = $query->result();
                $total = count($total) > 0 ? (int) $total[0]->total : 0;
                $pages = ceil($total / $perPage);

                $data = array_map(function ($e) {
                    $eMapper = new MainMapper($e->id);
                    return $eMapper->getBasicData();
                }, $data);

                $response_data['page'] = $page;
                $response_data['perPage'] = $perPage;
                $response_data['pages'] = $pages;
                $response_data['total'] = $total;
                $response_data['data'] = $data;

                return $response->withJson($response_data);

            } else {

                $query->execute();

                return $response->withJson($query->result());

            }

        } else {
            throw new NotFoundException($request, $response);
        }
    }

    /**
     * byCategory
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function byCategory(Request $request, Response $response, array $args)
    {

        if ($request->isXhr()) {

            $type = $request->getQueryParam('type', 'friendly_url');
            $category_value = $request->getAttribute('category', null);
            $exists = false;
            $category = null;

            if ($type == 'friendly_url') {
                $exists = CategoryMapper::existsByFriendlyURL((string) $category_value);
            } elseif ($type == 'id') {
                $category_value = !is_null($category_value) && ctype_digit($category_value) ? $category_value : -1;
                $exists = CategoryMapper::existsByID((int) $category_value);
            } elseif ($type == 'name') {
                $exists = CategoryMapper::existsByName((string) $category_value);
            }

            if ($exists) {
                $category = CategoryMapper::getBy($category_value, $type);
            }

            if ($category !== null) {
                return $response->withJson(MainMapper::allByCategory((int) $category->id));
            } else {
                throw new NotFoundException($request, $response);
            }
        } else {
            throw new NotFoundException($request, $response);
        }
    }

    /**
     * single
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function single(Request $request, Response $response, array $args)
    {

        if ($request->isXhr()) {

            $type = $request->getQueryParam('type', 'friendly_url');
            $search_value = $request->getAttribute('value', null);
            $exists = false;
            $article = null;

            if ($type == 'friendly_url') {
                $exists = MainMapper::existsByFriendlyURL($search_value);
            } elseif ($type == 'id') {
                $search_value = !is_null($search_value) && ctype_digit($search_value) ? $search_value : -1;
                $exists = MainMapper::existsByID((int) $search_value);
            }

            if ($exists) {
                $article = MainMapper::getBy($search_value, $type);
            }

            if ($article !== null) {
                return $response->withJson($article);
            } else {
                throw new NotFoundException($request, $response);
            }
        } else {
            throw new NotFoundException($request, $response);
        }
    }

    /**
     * dataTables
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function dataTables(Request $request, Response $response, array $args)
    {

        if ($request->isXhr()) {

            $columns_order = [
                'id',
                'title',
                'author',
                'category',
                'start_date',
                'end_date',
                'created',
                'updated',
                'visits',
            ];

            $select_fields = [
                "id",
                "title",
                "author",
                "category",
                "start_date",
                "end_date",
                "created",
                "updated",
                "CONVERT(JSON_EXTRACT(meta, '$.visits'), SIGNED) AS visits",
            ];

            DataTablesHelper::setTablePrefixOnOrder(false);
            DataTablesHelper::setTablePrefixOnSearch(false);

            $result = DataTablesHelper::process([
                'select_fields' => $select_fields,
                'columns_order' => $columns_order,
                'mapper' => new MainMapper(),
                'request' => $request,
                'on_set_data' => function ($e) {

                    $buttonEdit = new HtmlElement('a', 'Editar');
                    $buttonEdit->setAttribute('class', "ui button green");
                    $buttonEdit->setAttribute('href', self::routeName('forms-edit', [
                        'id' => $e->id,
                    ]));

                    if ($buttonEdit->getAttributes(false)->offsetExists('href')) {
                        $href = $buttonEdit->getAttributes(false)->offsetGet('href');
                        if (strlen(trim($href->getValue())) < 1) {
                            $buttonEdit = '';
                        }
                    }

                    $mapper = new MainMapper($e->id);

                    return [
                        $mapper->id,
                        strlen($mapper->title) > 50 ? trim(mb_substr($mapper->title, 0, 50)) . '...' : $mapper->title,
                        $mapper->author->username,
                        $mapper->category->name,
                        !is_null($mapper->start_date) ? $mapper->start_date->format('d-m-Y h:i:s') : '-',
                        !is_null($mapper->end_date) ? $mapper->end_date->format('d-m-Y h:i:s') : '-',
                        $mapper->created->format('d-m-Y h:i:s'),
                        !is_null($mapper->updated) ? $mapper->updated->format('d-m-Y h:i:s') : 'Nunca',
                        $e->visits,
                        (string) $buttonEdit,
                    ];
                },
            ]);

            return $response->withJson($result->getValues());
        } else {
            throw new NotFoundException($request, $response);
        }
    }

    /**
     * action
     *
     * Creación/Edición
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function action(Request $request, Response $response, array $args)
    {

        $id = $request->getParsedBodyParam('id', -1);
        $title = $request->getParsedBodyParam('title', null);
        $category = $request->getParsedBodyParam('category', null);
        $content = $request->getParsedBodyParam('content', null);
        $start_date = $request->getParsedBodyParam('start_date', '');
        $end_date = $request->getParsedBodyParam('end_date', '');
        $seoDescription = $request->getParsedBodyParam('seo_description', '');

        $start_date = strlen(trim($start_date)) > 0 ? date_create_from_format(self::FORMAT_DATETIME, $start_date) : null;
        $end_date = strlen(trim($end_date)) > 0 ? date_create_from_format(self::FORMAT_DATETIME, $end_date) : null;

        $is_edit = $id !== -1;

        $valid_params = !in_array(null, [
            $title,
            $category,
            $content,
        ]);

        $operation_name = $is_edit ? 'Modificar artículo' : 'Crear artículo';

        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $result->setValue('redirect', false);

        $error_parameters_message = 'Los parámetros recibidos son erróneos.';
        $not_exists_message = 'El artículo que intenta modificar no existe';
        $success_create_message = 'Artículo creado.';
        $success_edit_message = 'Datos guardados.';
        $unknow_error_message = 'Ha ocurrido un error desconocido.';
        $is_duplicate_message = 'Ya existe un artículo con ese nombre en la categoría seleccionada.';

        $redirect_url_on_create = self::routeName('list');

        if ($valid_params) {

            $title = clean_string($title);
            $friendly_url = MainMapper::generateFriendlyURL($title, $id);
            $content = clean_string($content);
            $seoDescription = clean_string($seoDescription);
            $folder = (new \DateTime)->format('Y/m/d/') . str_replace('.', '', uniqid());

            $is_duplicate = MainMapper::isDuplicate($title, $friendly_url, (int) $category, $id);

            if (!$is_duplicate) {

                if (!$is_edit) {

                    $mapper = new MainMapper();

                    try {

                        $imageMain = self::handlerUploadImage('image-main', $folder);
                        $imageThumb = self::handlerUploadImage('image-thumb', $folder);
                        $imageOpenGraph = self::handlerUploadImage('image-og', $folder);

                        $mapper->category = $category;
                        $mapper->title = $title;
                        $mapper->friendly_url = $friendly_url;
                        $mapper->content = $content;
                        $mapper->start_date = $start_date;
                        $mapper->end_date = $end_date;
                        $mapper->meta = [
                            "imageMain" => $imageMain,
                            "imageThumb" => $imageThumb,
                            "imageOpenGraph" => strlen($imageOpenGraph) > 0 ? $imageOpenGraph : '',
                            "seoDescription" => $seoDescription,
                            "folder" => $folder,
                            "visits" => 0,
                        ];
                        $saved = $mapper->save();

                        if ($saved) {

                            $mapper->id = $mapper->getLastInsertID();
                            $this->moveTemporaryImages($mapper);

                            $result->setMessage($success_create_message)
                                ->operation($operation_name)
                                ->setSuccess(true);

                            $result->setValue('redirect', true);
                            $result->setValue('redirect_to', $redirect_url_on_create);
                        } else {
                            $result->setMessage($unknow_error_message);
                        }
                    } catch (\Exception $e) {
                        $result->setMessage($e->getMessage());
                        $result->setValue('Exception', [
                            'message' => $e->getMessage(),
                            'code' => $e->getCode(),
                            'line' => $e->getLine(),
                            'file' => $e->getFile(),
                        ]);
                    }
                } else {

                    $mapper = new MainMapper((int) $id);
                    $exists = !is_null($mapper->id);

                    if ($exists) {

                        try {

                            $currentImageOpenGraph = isset($mapper->meta->imageOpenGraph) ? $mapper->meta->imageOpenGraph : null;
                            $currentImageOpenGraph = is_string($currentImageOpenGraph) && strlen($currentImageOpenGraph) > 0 ? $currentImageOpenGraph : null;
                            $currentSeoDescription = isset($mapper->meta->seoDescription) ? $mapper->meta->seoDescription : '';
                            $folder = isset($mapper->meta->folder) ? $mapper->meta->folder : $folder;

                            $imageMain = self::handlerUploadImage('image-main', $folder, $mapper->meta->imageMain);
                            $imageThumb = self::handlerUploadImage('image-thumb', $folder, $mapper->meta->imageThumb);
                            $imageOpenGraph = self::handlerUploadImage('image-og', $folder, $currentImageOpenGraph);

                            $oldText = $mapper->content;
                            $mapper->category = $category;
                            $mapper->title = $title;
                            $mapper->friendly_url = $friendly_url;
                            $mapper->content = $content;
                            $mapper->start_date = $start_date;
                            $mapper->end_date = $end_date;
                            $mapper->meta = [
                                "imageMain" => strlen($imageMain) > 0 ? $imageMain : $mapper->meta->imageMain,
                                "imageThumb" => strlen($imageThumb) > 0 ? $imageThumb : $mapper->meta->imageThumb,
                                "imageOpenGraph" => strlen($imageOpenGraph) > 0 ? $imageOpenGraph : $currentImageOpenGraph,
                                "seoDescription" => strlen($seoDescription) > 0 ? $seoDescription : $currentSeoDescription,
                                "folder" => $folder,
                                "visits" => $mapper->meta->visits,
                            ];
                            $updated = $mapper->update();

                            if ($updated) {

                                $this->moveTemporaryImages($mapper, $oldText);

                                $result->setValue('reload', true);

                                $result->setMessage($success_edit_message)
                                    ->operation($operation_name)
                                    ->setSuccess(true);
                            } else {
                                $result->setMessage($unknow_error_message);
                            }
                        } catch (\Exception $e) {
                            $result->setMessage($e->getMessage());
                            $result->setValue('Exception', [
                                'message' => $e->getMessage(),
                                'code' => $e->getCode(),
                                'line' => $e->getLine(),
                                'file' => $e->getFile(),
                            ]);
                        }
                    } else {
                        $result->setMessage($not_exists_message);
                    }
                }
            } else {

                $result->setMessage($is_duplicate_message);
            }
        } else {
            $result->setMessage($error_parameters_message);
        }

        return $response->withJson($result);
    }

    /**
     * handlerUploadImage
     *
     * @param string $nameOnFiles
     * @param string $folder
     * @param string $currentRoute
     * @param bool $setNameByInput
     * @return string
     */
    protected static function handlerUploadImage(string $nameOnFiles, string $folder, string $currentRoute = null, bool $setNameByInput = true)
    {
        $handler = new FileUpload($nameOnFiles, [
            FileValidator::TYPE_ALL_IMAGES,
        ]);
        $valid = false;
        $relativeURL = '';

        $name = 'file_' . uniqid();
        $oldFile = null;

        if ($handler->hasInput()) {

            try {

                $valid = $handler->validate();

                $uploadDirPath = (new static )->uploadDir;
                $uploadDirRelativeURL = (new static )->uploadDirURL;

                if ($setNameByInput && $valid) {

                    $name = $_FILES[$nameOnFiles]['name'];
                    $lastPointIndex = strrpos($name, '.');

                    if ($lastPointIndex !== false) {
                        $name = substr($name, 0, $lastPointIndex);
                    }

                }

                if (!is_null($currentRoute)) {
                    //Si ya existe
                    $oldFile = append_to_url(basepath(), $currentRoute);
                    $oldFile = file_exists($oldFile) ? $oldFile : null;
                }

                $uploadDirPath = append_to_url($uploadDirPath, $folder);
                $uploadDirRelativeURL = append_to_url($uploadDirRelativeURL, $folder);

                if ($valid) {

                    $locations = $handler->moveTo($uploadDirPath, $name, null, false, true);

                    if (count($locations) > 0) {

                        $url = $locations[0];
                        $nameCurrent = basename($url);
                        $relativeURL = trim(append_to_url($uploadDirRelativeURL, $nameCurrent), '/');

                        //Eliminar archivo anterior
                        if (!is_null($oldFile)) {
                            unlink($oldFile);
                        }

                        //Se elimina cualquier otro archivo
                        foreach ($locations as $file) {
                            if ($url != $file) {
                                if (is_string($file) && file_exists($file)) {
                                    unlink($file);
                                }
                            }
                        }

                    }

                } else {
                    throw new \Exception(implode('<br>', $handler->getErrorMessages()));
                }

            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }

        }

        return $relativeURL;
    }

    /**
     * quillImageHandler
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function quillImageHandler(Request $request, Response $response, array $args)
    {
        $files_uploaded = $request->getUploadedFiles();
        $image = isset($files_uploaded['image']) ? $files_uploaded['image'] : null;

        $result = new ResultOperations([
            'uploadImage' => new Operation('uploadImage'),
        ]);

        $result->setValue('path', null);

        if (!is_null($image)) {

            $images = is_array($image) ? $image : [$image];

            foreach ($images as $image) {

                if ($image->getError() === UPLOAD_ERR_OK) {

                    $filename = move_uploaded_file_to($this->uploadTmpDir, $image, uniqid());

                    $url = append_to_url(base_url($this->uploadDirTmpURL), $filename);

                    if (!is_null($filename)) {
                        $result
                            ->operation('uploadImage')
                            ->setMessage('Imagen subida')
                            ->setSuccess(true);
                        $result->setValue('path', $url);
                    } else {
                        $result
                            ->operation('uploadImage')
                            ->setMessage('La imagen no pudo ser subida, intente después.');
                    }
                }
            }
        } else {
            $result
                ->operation('uploadImage')
                ->setMessage('No se ha subido ninguna imagen.');
        }

        return $response->withJson($result);
    }

    /**
     * moveTemporaryImages
     *
     * @param MainMapper $entity
     * @param string $oldText
     * @return void
     */
    protected function moveTemporaryImages(MainMapper &$entity, string $oldText = null)
    {
        $imagesOnText = [];
        $imagesOnOldText = [];
        $currentImagesOnText = [];

        $isEdit = !is_null($oldText) && strlen($oldText) > 0;
        $id = $entity->id;

        $regex = '/https?\:\/\/[^\",]+/i';

        preg_match_all($regex, $entity->content, $imagesOnText);

        $imagesOnText = $imagesOnText[0];

        if (count($imagesOnText) > 0) {

            foreach ($imagesOnText as $url) {

                if (strpos($url, $this->uploadDirTmpURL) !== false) {

                    $filename = basename($url);

                    $oldPath = append_to_url($this->uploadTmpDir, "$filename");

                    $newFolder = append_to_url($this->uploadDir, "$id");

                    $newPath = append_to_url($newFolder, "$filename");

                    if (!file_exists($newFolder)) {
                        make_directory($newFolder);
                    }

                    if (file_exists($oldPath)) {
                        rename($oldPath, $newPath);
                    }

                    $_url = append_to_url($this->uploadDirURL, "$id/$filename");
                    $_url = trim($_url, '/');

                    $entity->content = str_replace($url, $_url, $entity->content);

                    $currentImagesOnText[] = $_url;
                } elseif (strpos($url, $this->uploadDirURL) !== false) {

                    $currentImagesOnText[] = trim($url, '/');
                }
            }
        }

        $updated = $entity->update();

        if ($isEdit) {

            preg_match_all($regex, $oldText, $imagesOnOldText);
            $imagesOnOldText = $imagesOnOldText[0];

            if ($updated && count($imagesOnOldText) > 0) {

                foreach ($imagesOnOldText as $url) {

                    if (!in_array($url, $currentImagesOnText)) {

                        $filename = str_replace($this->uploadDirURL, '', $url);

                        $path = append_to_url($this->uploadDir, $filename);

                        if (file_exists($path)) {
                            unlink($path);
                        }
                    }
                }
            }
        }
    }

    /**
     * routeName
     *
     * @param string $name
     * @param array $params
     * @param bool $silentOnNotExists
     * @return string
     */
    public static function routeName(string $name = null, array $params = [], bool $silentOnNotExists = false)
    {
        if (!is_null($name)) {
            $name = trim($name);
            $name = strlen($name) > 0 ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$prefixParentEntity . '-' . self::$prefixEntity . $name : self::$prefixParentEntity;

        $allowed = false;
        $current_user = get_config('current_user');

        if ($current_user != false) {
            $allowed = Roles::hasPermissions($name, (int) $current_user->type);
        } else {
            $allowed = true;
        }

        if ($allowed) {
            return get_route(
                $name,
                $params,
                $silentOnNotExists
            );
        } else {
            return '';
        }
    }

    /**
     * deleteOrphanFiles
     *
     * @return void
     */
    protected function deleteOrphanFiles()
    {
        $temporary_directory = new DirectoryObject($this->uploadTmpDir);
        $temporary_directory->process();
        $temporary_directory->delete();
    }

    /**
     * routes
     *
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        if (PIECES_PHP_BLOG_ENABLED) {

            $from = \DateTime::createFromFormat('d-m-Y h:i:s A', date('d-m-Y') . ' 2:00:00 AM');
            $to = \DateTime::createFromFormat('d-m-Y h:i:s A', date('d-m-Y') . ' 4:00:00 AM');
            $now = new \DateTime();
            $valid_interval = $now >= $from && $now <= $to;

            if ($valid_interval) {
                $instance = new static;
                $instance->deleteOrphanFiles();
            }

            $routes = [];

            $groupSegmentURL = $group->getGroupSegment();

            $lastIsBar = last_char($groupSegmentURL) == '/';
            $startRoute = $lastIsBar ? '' : '/';

            $roles_manage_permission = [
                UsersModel::TYPE_USER_ROOT,
                UsersModel::TYPE_USER_ADMIN,
            ];

            $group->active(PIECES_PHP_BLOG_ENABLED);
            $group->register($routes);

            //Rutas básicas
            $group->register(
                self::genericManageRoutes($startRoute, self::$prefixParentEntity, self::class, self::$prefixEntity, $roles_manage_permission, true)
            );

            //Otras rutas
            $namePrefix = self::$prefixParentEntity . '-' . self::$prefixEntity;
            $startRoute .= self::$prefixEntity;
            $group->register([
                new Route(
                    "{$startRoute}/list/{category}",
                    self::class . ":byCategory",
                    "{$namePrefix}-ajax-all-category",
                    'GET'
                ),
            ]);
            $group->register([
                new Route(
                    "{$startRoute}/single/{value}",
                    self::class . ":single",
                    "{$namePrefix}-ajax-single",
                    'GET'
                ),
            ]);

            //Rutas categorías
            $group = CategoryController::routes($group);
        }

        return $group;
    }

    /**
     * genericManageRoutes
     *
     * @param string $startRoute
     * @param string $namePrefix
     * @param string $handler
     * @param string $uriPrefix
     * @param array $rolesAllowed
     * @return Route[]
     */
    protected static function genericManageRoutes(string $startRoute, string $namePrefix, string $handler, string $uriPrefix, array $rolesAllowed = [], bool $withQuillHandler = false)
    {
        $namePrefix .= '-' . $uriPrefix;
        $startRoute .= $uriPrefix;
        $all_roles = array_keys(UsersModel::TYPES_USERS);

        $routes = [
            new Route(
                "{$startRoute}",
                "{$handler}:all",
                "{$namePrefix}-ajax-all",
                'GET'
            ),
            new Route(
                "{$startRoute}/datatables[/]",
                "{$handler}:dataTables",
                "{$namePrefix}-datatables",
                'GET'
            ),
            new Route(
                "{$startRoute}/list[/]",
                "{$handler}:listView",
                "{$namePrefix}-list",
                'GET',
                true,
                null,
                $all_roles
            ),
            new Route(
                "{$startRoute}/forms/add[/]",
                "{$handler}:addForm",
                "{$namePrefix}-forms-add",
                'GET',
                true,
                null,
                $rolesAllowed
            ),
            new Route(
                "{$startRoute}/action/add[/]",
                "{$handler}:action",
                "{$namePrefix}-actions-add",
                'POST',
                true,
                null,
                $rolesAllowed
            ),
            new Route(
                "{$startRoute}/forms/edit/{id}[/]",
                "{$handler}:editForm",
                "{$namePrefix}-forms-edit",
                'GET',
                true,
                null,
                $rolesAllowed
            ),
            new Route(
                "{$startRoute}/action/edit[/]",
                "{$handler}:action",
                "{$namePrefix}-actions-edit",
                'POST',
                true,
                null,
                $rolesAllowed
            ),
        ];

        if ($withQuillHandler) {
            $routes[] = new Route(
                "{$startRoute}/quill-image-upload[/]",
                "{$handler}:quillImageHandler",
                "{$namePrefix}-image-handler",
                'POST',
                true,
                null,
                $rolesAllowed
            );
        }

        return $routes;
    }
}
