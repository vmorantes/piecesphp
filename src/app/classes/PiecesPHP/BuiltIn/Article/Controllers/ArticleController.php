<?php
/**
 * ArticleController.php
 */

namespace PiecesPHP\BuiltIn\Article\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use PiecesPHP\BuiltIn\Article\Category\Controllers\CategoryController;
use PiecesPHP\BuiltIn\Article\Category\Mappers\CategoryContentMapper;
use PiecesPHP\BuiltIn\Article\Category\Mappers\CategoryMapper;
use PiecesPHP\BuiltIn\Article\Mappers\ArticleContentMapper;
use PiecesPHP\BuiltIn\Article\Mappers\ArticleMapper;
use PiecesPHP\BuiltIn\Article\Mappers\ArticleViewMapper;
use PiecesPHP\Core\Cache\CacheControllersCriteries;
use PiecesPHP\Core\Cache\CacheControllersCritery;
use PiecesPHP\Core\Cache\CacheControllersManager;
use PiecesPHP\Core\Forms\FileUpload;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
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

        self::$title = __('articlesBackend', self::$title);
        self::$pluralTitle = __('articlesBackend', self::$pluralTitle);

        parent::__construct(false); //No cargar ningún modelo automáticamente.

        $this->model = (new ArticleViewMapper())->getModel();
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

        $lang = $request->getAttribute('lang', get_config('app_lang'));

        if (!in_array($lang, get_config('allowed_langs'))) {
            throw new NotFoundException($request, $response);
        }

        set_custom_assets([
            'statics/js/built-in/article/backend/forms.js',
        ], 'js');

        $action = self::routeName('actions-add');
        $back_link = self::routeName('list');
        $quill_proccesor_link = self::routeName('image-handler');
        $options_categories = array_to_html_options(CategoryContentMapper::allForSelect(), null);

        $data = [];
        $data['action'] = $action;
        $data['back_link'] = $back_link;
        $data['options_categories'] = $options_categories;
        $data['quill_proccesor_link'] = $quill_proccesor_link;
        $data['lang'] = $lang;
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

        $lang = $request->getAttribute('lang', null);
        $id = $request->getAttribute('id', null);
        $id = !is_null($id) && ctype_digit($id) ? (int) $id : null;

        $langExists = $lang !== null ? in_array($lang, get_config('allowed_langs')) : true;

        $element = new ArticleMapper($id);

        if (!is_null($element->id) && $langExists) {

            if (!is_null($lang)) {

                $subElement = ArticleViewMapper::getContentByLang($element->id, $lang, true);

                if (is_null($subElement)) {
                    $subElement = new ArticleContentMapper();
                    $subElement->lang = $lang;
                }

            } else {
                $subElement = ArticleViewMapper::getContentByLang($element->id, get_config('app_lang'), true);
                $subElement = !is_null($subElement) ? $subElement : ArticleViewMapper::getByPreferedSubID($element->id);
            }

            $action = self::routeName('actions-edit');
            $back_link = self::routeName('list');
            $quill_proccesor_link = self::routeName('image-handler');
            $options_categories = array_to_html_options(CategoryContentMapper::allForSelect(), $element->category->id);

            $data = [];
            $data['action'] = $action;
            $data['element'] = $element;
            $data['subElement'] = $subElement;
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
        set_custom_assets([
            'statics/js/built-in/article/backend/list.js',
        ], 'js');

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

            //Opciones recibidas
            $allDate = $request->getQueryParam('all_date', null);
            $paginate = $request->getQueryParam('paginate', null);
            $page = $request->getQueryParam('page', 1);
            $perPage = $request->getQueryParam('per_page', 5);
            $onlyLang = $request->getQueryParam('lang', null);

            //Tratamiento de las opciones
            $allowedLangs = get_config('allowed_langs');

            $onlyLang = is_string($onlyLang) && strlen(trim($onlyLang)) > 0 ? trim($onlyLang) : null;
            $onlyLang = !is_null($onlyLang) && in_array($onlyLang, $allowedLangs) ? $onlyLang : get_config('app_lang');

            $page = is_integer($page) || ctype_digit($page) ? (int) $page : 1;
            $perPage = is_integer($perPage) || ctype_digit($perPage) ? (int) $perPage : 5;

            //=================Definir política de cache=================//
            $cacheHandler = new CacheControllersManager(self::class, 'all', 3600 * 24);

            $cacheCriteries = new CacheControllersCriteries([
                new CacheControllersCritery('allDate', $allDate),
                new CacheControllersCritery('paginate', $paginate),
                new CacheControllersCritery('page', $page),
                new CacheControllersCritery('perPage', $perPage),
                new CacheControllersCritery('onlyLang', $onlyLang),
            ]);

            $cacheHandler->setCriteries($cacheCriteries);

            $cacheHandler->process();

            $hasCache = $cacheHandler->hasCachedData();
            //=================Fin de política de cache=================//

            if (!$hasCache) {

                //Prepación de la consulta
                $model = ArticleViewMapper::model();
                $query = $model->select()->orderBy('start_date DESC, end_date DESC, created DESC');

                $now = date('Y-m-d H:i:s');

                $where = [
                    "(start_date <= '{$now}' OR start_date IS NULL) AND",
                    "(end_date > '{$now}' OR end_date IS NULL)",
                    "AND lang = '$onlyLang'",
                ];

                $where = implode(' ', $where);

                if ($allDate !== 'yes') {
                    $query->where($where);
                }

                if ($paginate === 'yes') {

                    $response_data = [];

                    $response_data['sql'] = $query->getCompiledSQL();
                    $query->execute(false, $page, $perPage);

                    $data = $query->result();

                    $query->resetAll();

                    $query->select('COUNT(id) AS total');

                    if ($allDate !== 'yes') {
                        $query->where($where);
                    }

                    $query->execute();
                    $total = $query->result();
                    $total = count($total) > 0 ? (int) $total[0]->total : 0;
                    $pages = ceil($total / $perPage);

                    $data = array_map(function ($e) {
                        $eMapper = new ArticleViewMapper($e->sub_id);
                        $i = $eMapper->getBasicData();
                        $i->title = htmlentities($eMapper->title);
                        $i->category->name = htmlentities(stripslashes($i->category->name));
                        return $i;
                    }, $data);

                    $response_data['page'] = $page;
                    $response_data['perPage'] = $perPage;
                    $response_data['pages'] = $pages;
                    $response_data['total'] = $total;
                    $response_data['data'] = $data;

                    $cacheHandler->setDataCache(json_encode($response_data), CacheControllersManager::CONTENT_TYPE_JSON);

                    return $response->withJson($response_data);

                } else {

                    $query->execute();

                    $result = $query->result();

                    $cacheHandler->setDataCache(json_encode($result), CacheControllersManager::CONTENT_TYPE_JSON);

                    return $response->withJson($result);

                }

            } else {

                return $response
                    ->write($cacheHandler->getCachedData(false))
                    ->withHeader('Content-Type', $cacheHandler->getContentType());
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

            //Opciones recibidas
            $type = $request->getQueryParam('type', 'friendly_url');
            $category_value = $request->getAttribute('category', null);
            $allDate = $request->getQueryParam('all_date', null);
            $paginate = $request->getQueryParam('paginate', null);
            $page = $request->getQueryParam('page', 1);
            $perPage = $request->getQueryParam('per_page', 5);
            $onlyLang = $request->getQueryParam('lang', null);

            $exists = false;
            $category = null;

            //Tratamiento de las opciones
            $allowedLangs = get_config('allowed_langs');

            $onlyLang = is_string($onlyLang) && strlen(trim($onlyLang)) > 0 ? trim($onlyLang) : null;
            $onlyLang = !is_null($onlyLang) && in_array($onlyLang, $allowedLangs) ? $onlyLang : get_config('app_lang');

            $page = is_integer($page) || ctype_digit($page) ? (int) $page : 1;
            $perPage = is_integer($perPage) || ctype_digit($perPage) ? (int) $perPage : 5;

            if ($type == 'friendly_url') {

                $exists = CategoryContentMapper::existsByFriendlyURL((string) $category_value);

            } elseif ($type == 'id' || $type == 'content_of') {

                $category_value = !is_null($category_value) && ctype_digit($category_value) ? $category_value : -1;
                $exists = CategoryMapper::existsByID((int) $category_value);
                $type = 'content_of';

            } elseif ($type == 'name') {

                $exists = CategoryContentMapper::existsByName((string) $category_value);

            }

            if ($exists) {
                $category = CategoryContentMapper::getBy($category_value, $type);
            }

            if ($category !== null) {

                //Prepación de la consulta
                $model = ArticleViewMapper::model();
                $query = $model->select()->orderBy('start_date DESC, end_date DESC, created DESC');

                $now = date('Y-m-d H:i:s');

                $where = [
                    "category = '{$category->content_of}'",
                    "AND lang = '$onlyLang'",
                ];

                if ($allDate !== 'yes') {
                    $where[] = "AND (start_date <= '{$now}' OR start_date IS NULL)";
                    $where[] = "AND (end_date > '{$now}' OR end_date IS NULL)";
                }

                $where = implode(' ', $where);
                $query->where($where);

                if ($paginate === 'yes') {

                    $response_data = [];

                    $response_data['sql'] = $query->getCompiledSQL();
                    $query->execute(false, $page, $perPage);

                    $data = $query->result();

                    $query->resetAll();

                    $query->select('COUNT(id) AS total');

                    if ($allDate !== 'yes') {
                        $query->where($where);
                    }

                    $query->execute();
                    $total = $query->result();
                    $total = count($total) > 0 ? (int) $total[0]->total : 0;
                    $pages = ceil($total / $perPage);

                    $data = array_map(function ($e) {
                        $eMapper = new ArticleViewMapper($e->sub_id);
                        $i = $eMapper->getBasicData();
                        $i->title = htmlentities($eMapper->title);
                        $i->category->name = htmlentities(stripslashes($i->category->name));
                        return $i;
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
                $exists = ArticleViewMapper::existsByFriendlyURL($search_value);
            } elseif ($type == 'id') {
                $search_value = !is_null($search_value) && ctype_digit($search_value) ? $search_value : -1;
                $exists = ArticleViewMapper::existsByID((int) $search_value);
            }

            if ($exists) {
                $article = ArticleViewMapper::getBy($search_value, $type);
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

            $ids = ArticleViewMapper::getPreferedsSubIDs();
            $where_string = count($ids) > 0 ? "sub_id = '" . implode("' OR sub_id = '", $ids) . "'" : '';

            DataTablesHelper::setTablePrefixOnOrder(false);
            DataTablesHelper::setTablePrefixOnSearch(false);

            $result = DataTablesHelper::process([
                'where_string' => $where_string,
                'columns_order' => $columns_order,
                'mapper' => new ArticleViewMapper(),
                'request' => $request,
                'on_set_data' => function ($e) {
                    return [
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                    ];
                },
            ]);

            $rawData = $result->getValue('rawData');

            foreach ($rawData as $index => $element) {

                $mapper = new ArticleViewMapper($element->sub_id);

                $rawData[$index] = $this->render(
                    'panel/' . self::$prefixParentEntity . '/' . self::$prefixSingularEntity . '/util/article-card',
                    [
                        'mapper' => $mapper,
                        'editLink' => self::routeName('forms-edit', [
                            'id' => $mapper->id,
                        ]),
                    ],
                    false
                );

            }

            $result->setValue('received', $request->getQueryParams());
            $result->setValue('rawData', $rawData);

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

        $contentOf = $request->getParsedBodyParam('content_of', -1);
        $subID = $request->getParsedBodyParam('id', -1);
        $lang = $request->getParsedBodyParam('lang', get_config('app_lang'));
        $title = $request->getParsedBodyParam('title', null);
        $category = $request->getParsedBodyParam('category', null);
        $content = $request->getParsedBodyParam('content', null);
        $start_date = $request->getParsedBodyParam('start_date', '');
        $end_date = $request->getParsedBodyParam('end_date', '');
        $seoDescription = $request->getParsedBodyParam('seo_description', '');

        $start_date = strlen(trim($start_date)) > 0 ? date_create_from_format(self::FORMAT_DATETIME, $start_date) : null;
        $end_date = strlen(trim($end_date)) > 0 ? date_create_from_format(self::FORMAT_DATETIME, $end_date) : null;

        $isEdit = $contentOf !== -1;

        $valid_params = !in_array(null, [
            $title,
            $category,
            $content,
        ]);

        $operationName = $isEdit ? __('articlesBackend', 'Modificar artículo') : __('articlesBackend', 'Crear artículo');

        $result = new ResultOperations([
            new Operation($operationName),
        ], $operationName);

        $result->setValue('redirect', false);

        $errorParametersMessage = __('articlesBackend', 'Los parámetros recibidos son erróneos.');
        $notExistsMessage = __('articlesBackend', 'El artículo que intenta modificar no existe');
        $successCreatedMessage = __('articlesBackend', 'Artículo creado.');
        $successEditMessage = __('articlesBackend', 'Datos guardados.');
        $unknowErrorMessage = __('articlesBackend', 'Ha ocurrido un error desconocido.');
        $isDuplicateMessage = __('articlesBackend', 'Ya existe un artículo con ese nombre en la categoría seleccionada.');
        $unexistsLangMessages = __('articlesBackend', 'El idioma que intenta usar no existe.');

        if ($valid_params) {

            $title = clean_string($title);
            $friendly_url = ArticleViewMapper::generateFriendlyURL($title, $subID);
            $content = clean_string($content);
            $seoDescription = clean_string($seoDescription);
            $folder = (new \DateTime)->format('Y/m/d/') . str_replace('.', '', uniqid());

            $isDuplicate = ArticleViewMapper::isDuplicate(
                $title,
                $friendly_url,
                (int) $category,
                $contentOf,
                $subID
            );

            if (in_array($lang, get_config('allowed_langs'))) {

                if (!$isDuplicate) {

                    if (!$isEdit) {

                        $mainMapper = new ArticleMapper();
                        $subMapper = new ArticleContentMapper();

                        try {

                            //Configuraciones del artículo en general
                            $mainMapper->category = $category;
                            $mainMapper->start_date = $start_date;
                            $mainMapper->end_date = $end_date;
                            $mainMapper->folder = $folder;
                            $mainMapper->visits = 0;

                            //Imágenes
                            $imageMain = self::handlerUploadImage('image-main', $folder);
                            $imageThumb = self::handlerUploadImage('image-thumb', $folder);
                            $imageOpenGraph = self::handlerUploadImage('image-og', $folder);
                            $mainMapper->images = [
                                "imageMain" => $imageMain,
                                "imageThumb" => $imageThumb,
                                "imageOpenGraph" => strlen($imageOpenGraph) > 0 ? $imageOpenGraph : '',
                            ];

                            $articleSaved = $mainMapper->save();
                            $articleInsertedID = $mainMapper->getLastInsertID();

                            if ($articleSaved) {

                                //Configuraciones del artículo en el idioma específico
                                $subMapper->content_of = $articleInsertedID;
                                $subMapper->lang = $lang;
                                $subMapper->title = $title;
                                $subMapper->friendly_url = $friendly_url;
                                $subMapper->content = $content;
                                $subMapper->seo_description = $seoDescription;

                                $subMapperSaved = $subMapper->save();

                                $subMapper->id = $subMapper->getLastInsertID();

                                if ($subMapperSaved) {

                                    $this->moveTemporaryImages($subMapper);

                                    $result->setMessage($successCreatedMessage)
                                        ->operation($operationName)
                                        ->setSuccess(true);

                                    $result->setValue('redirect', true);
                                    $result->setValue('redirect_to', self::routeName('forms-edit', ['id' => $articleInsertedID]));

                                } else {

                                    $result->setMessage($unknowErrorMessage);

                                }

                            } else {

                                $result->setMessage($unknowErrorMessage);

                            }

                        } catch (\Exception $e) {
                            $result->setMessage($e->getMessage());
                            $result->setValue('Exception', [
                                'message' => $e->getMessage(),
                                'code' => $e->getCode(),
                                'line' => $e->getLine(),
                                'file' => $e->getFile(),
                            ]);
                            log_exception($e);
                        }

                    } else {

                        $mainMapper = new ArticleMapper((int) $contentOf);
                        $subMapper = new ArticleContentMapper((int) $subID);

                        $exists = $subID == -1 ? !is_null($mainMapper->id) : !is_null($mainMapper->id) && !is_null($subMapper->id);

                        if ($exists) {

                            try {

                                //Configuraciones del artículo en general
                                $mainMapper->category = $category;
                                $mainMapper->start_date = $start_date;
                                $mainMapper->end_date = $end_date;

                                //Imágenes
                                $imageMain = self::handlerUploadImage(
                                    'image-main',
                                    $mainMapper->folder,
                                    $mainMapper->images->imageMain
                                );
                                $imageThumb = self::handlerUploadImage(
                                    'image-thumb',
                                    $mainMapper->folder,
                                    $mainMapper->images->imageThumb
                                );
                                $imageOpenGraph = self::handlerUploadImage(
                                    'image-og',
                                    $mainMapper->folder,
                                    $mainMapper->images->imageOpenGraph
                                );
                                $mainMapper->images = [
                                    "imageMain" => strlen($imageMain) > 0 ? $imageMain : $mainMapper->images->imageMain,
                                    "imageThumb" => strlen($imageThumb) > 0 ? $imageThumb : $mainMapper->images->imageThumb,
                                    "imageOpenGraph" => strlen($imageOpenGraph) > 0 ? $imageOpenGraph : $mainMapper->images->imageOpenGraph,
                                ];

                                $articleUpdated = $mainMapper->update();

                                if ($articleUpdated) {

                                    //Configuraciones del artículo en el idioma específico
                                    $subMapper->lang = $lang;
                                    $subMapper->title = $title;
                                    $subMapper->friendly_url = $friendly_url;
                                    $subMapper->seo_description = $seoDescription;

                                    $successAction = false;
                                    $oldText = null;

                                    if ($subMapper->id !== null) {

                                        $oldText = $subMapper->content;
                                        $subMapper->content = $content;
                                        $successAction = $subMapper->update();

                                    } else {

                                        $subMapper->content = $content;
                                        $subMapper->content_of = $mainMapper->id;
                                        $successAction = $subMapper->save();
                                        $subMapper->id = $subMapper->getLastInsertID();

                                    }

                                    if ($successAction) {

                                        $this->moveTemporaryImages($subMapper, $oldText);

                                        $result->setValue('reload', true);

                                        $result->setMessage($successEditMessage)
                                            ->operation($operationName)
                                            ->setSuccess(true);

                                    } else {

                                        $result->setMessage($unknowErrorMessage);

                                    }

                                } else {

                                    $result->setMessage($unknowErrorMessage);

                                }

                            } catch (\Exception $e) {
                                $result->setMessage($e->getMessage());
                                $result->setValue('Exception', [
                                    'message' => $e->getMessage(),
                                    'code' => $e->getCode(),
                                    'line' => $e->getLine(),
                                    'file' => $e->getFile(),
                                ]);
                                log_exception($e);
                            }
                        } else {
                            $result->setMessage($notExistsMessage);
                        }
                    }

                } else {

                    $result->setMessage($isDuplicateMessage);
                }

            } else {
                $result->setMessage($unexistsLangMessages);
            }

        } else {
            $result->setMessage($errorParametersMessage);
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

                            if (basename($oldFile) != $nameCurrent) {
                                unlink($oldFile);
                            }

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
                            ->setMessage(__('articlesBackend', 'Imagen subida'))
                            ->setSuccess(true);
                        $result->setValue('path', $url);
                    } else {
                        $result
                            ->operation('uploadImage')
                            ->setMessage(__('articlesBackend', 'La imagen no pudo ser subida, intente después.'));
                    }
                }
            }
        } else {
            $result
                ->operation('uploadImage')
                ->setMessage(__('articlesBackend', 'No se ha subido ninguna imagen.'));
        }

        return $response->withJson($result);
    }

    /**
     * moveTemporaryImages
     *
     * @param ArticleContentMapper $entity
     * @param string $oldText
     * @return void
     */
    protected function moveTemporaryImages(ArticleContentMapper &$entity, string $oldText = null)
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
                $rolesAllowed
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
                "{$startRoute}/forms/add/{lang}[/]",
                "{$handler}:addForm",
                "{$namePrefix}-forms-add-lang",
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
                "{$startRoute}/forms/edit/{id}/{lang}[/]",
                "{$handler}:editForm",
                "{$namePrefix}-forms-edit-lang",
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
