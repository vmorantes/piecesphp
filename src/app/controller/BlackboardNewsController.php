<?php

/**
 * BlackboardNewsController.php
 */

namespace App\Controller;

use App\Model\BlackboardNewsModel;
use App\Model\UsersModel;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use Slim\Exception\NotFoundException;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * BlackboardNewsController.
 *
 * Controlador de tablón de noticias
 *
 * @package     App\Controller
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class BlackboardNewsController extends AdminPanelController
{

    const UPLOAD_DIR = 'blackboard';
    const UPLOAD_DIR_TMP = 'blackboard/tmp';
    const FORMAT_DATETIME = 'd-m-Y h:i A';

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
        add_global_asset(BLACKBOARD_NEWS_PATH_JS . '/main.js', 'js');

        $this->uploadDir = append_to_url(get_config('upload_dir'), self::UPLOAD_DIR);
        $this->uploadTmpDir = append_to_url(get_config('upload_dir'), self::UPLOAD_DIR_TMP);
        $this->uploadDirURL = append_to_url(get_config('upload_dir_url'), self::UPLOAD_DIR);
        $this->uploadDirTmpURL = append_to_url(get_config('upload_dir_url'), self::UPLOAD_DIR_TMP);
    }

    /**
     * listView
     *
     * Vista de listado de noticias
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return void
     */
    public function listView(Request $req, Response $res, array $args)
    {

        $this->render('panel/layout/header');
        $this->render(BLACKBOARD_NEWS_PATH_VIEWS . '/list');
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * writeForm
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function writeForm(Request $request, Response $response, array $args)
    {

        $types = Roles::getRolesIdentifiers(true);

        import_quilljs(['imageUpload', 'imageResize']);

        $this->render(ADMIN_PATH_VIEWS . '/layout/header');
        $this->render(BLACKBOARD_NEWS_PATH_VIEWS . '/create', [
            'types' => $types,
        ]);
        $this->render(ADMIN_PATH_VIEWS . '/layout/footer');

        return $response;
    }

    /**
     * editForm
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function editForm(Request $request, Response $response, array $args)
    {

        $id = isset($args['id']) ? $args['id'] : null;
        $id = ctype_digit($id) ? (int) $id : null;
        $types = Roles::getRolesIdentifiers(true);
        $new = new BlackboardNewsModel($id);

        if (!is_null($new->id)) {

            import_quilljs(['imageUpload', 'imageResize']);

            $this->render(ADMIN_PATH_VIEWS . '/layout/header');
            $this->render(BLACKBOARD_NEWS_PATH_VIEWS . '/edit', [
                'types' => $types,
                'new' => $new,
            ]);
            $this->render(ADMIN_PATH_VIEWS . '/layout/footer');

            return $response;
        } else {
            throw new NotFoundException($request, $response);
        }
    }

    /**
     * deleteNew
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function deleteNew(Request $request, Response $response, array $args)
    {

        $id = isset($args['id']) ? $args['id'] : null;
        $id = ctype_digit($id) ? (int) $id : null;
        $new = new BlackboardNewsModel($id);

        $result = new ResultOperations([
            'deleteNew' => new Operation('deleteNew'),
        ], __('news', 'Eliminación de noticia'));

        if (!is_null($new->id)) {

            $deleted = $new->getModel()->delete([
                'id' => $id,
            ])->execute();

            if ($deleted) {
                $directory = append_to_url($this->uploadDir, (string) $id);
                remove_directory($directory);
            }

            $result
                ->setMessage($deleted ? __('news', 'La noticia ha sido eliminada.') : __('news', 'La noticia no pudo ser eliminada, intente luego.'))
                ->operation('deleteNew')
                ->setSuccess($deleted);

        } else {
            $result->setMessage(__('news', 'La noticia no existe'));
        }
        return $response->withJson($result);
    }

    /**
     * registerNew
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function registerNew(Request $request, Response $response, array $args)
    {

        $type = $request->getParsedBodyParam('type', null);
        $title = $request->getParsedBodyParam('title', null);
        $text = $request->getParsedBodyParam('text', null);
        $start_date = $request->getParsedBodyParam('start_date', null);
        $end_date = $request->getParsedBodyParam('end_date', null);

        $start_date = strlen(trim($start_date)) > 0 ? date_create_from_format(self::FORMAT_DATETIME, $start_date) : null;
        $end_date = strlen(trim($end_date)) > 0 ? date_create_from_format(self::FORMAT_DATETIME, $end_date) : null;

        $params_verify = !in_array(null, [
            $type,
            $title,
            $text,
        ]);

        $result = (new ResultOperations([
            'registerNew' => new Operation('registerNew'),
        ]))->setValue('redirect', '');

        if ($params_verify) {

            $entity = new BlackboardNewsModel();
            $entity->author = $this->user->id;
            $entity->type = $type;
            $entity->title = $title;
            $entity->start_date = $start_date;
            $entity->text = $text;
            $entity->end_date = $end_date;
            $saved = $entity->save();
            $id = $entity->getLastInsertID();
            $entity->id = $id;

            $redirect = '';
            if ($saved) {
                $this->moveTemporaryImages($entity);
                $redirect = get_route('blackboard-news-list');
            }

            $result
                ->setValue('redirect', $redirect)
                ->setMessage($saved ? __('news', 'La noticia ha sido creada') : __('news', 'No se ha podido crear la noticia, intente más tarde.'))
                ->operation('registerNew')
                ->setSuccess($saved);

        } else {
            $result
                ->setMessage(__('news', 'Los parámetros recibidos no son correctos'))
                ->operation('registerNew');
        }

        return $response->withJson($result);
    }

    /**
     * editNew
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function editNew(Request $request, Response $response, array $args)
    {

        $id = $request->getParsedBodyParam('id', null);
        $id = ctype_digit($id) ? (int) $id : null;
        $author = $request->getParsedBodyParam('author', null);
        $type = $request->getParsedBodyParam('type', null);
        $title = $request->getParsedBodyParam('title', null);
        $text = $request->getParsedBodyParam('text', null);
        $start_date = $request->getParsedBodyParam('start_date', null);
        $end_date = $request->getParsedBodyParam('end_date', null);

        $start_date = strlen(trim($start_date)) > 0 ? date_create_from_format(self::FORMAT_DATETIME, $start_date) : null;
        $end_date = strlen(trim($end_date)) > 0 ? date_create_from_format(self::FORMAT_DATETIME, $end_date) : null;

        $params_verify = !in_array(null, [
            $id,
            $author,
            $type,
            $title,
            $text,
        ]);

        $result = new ResultOperations([
            'editNew' => new Operation('editNew'),
        ]);

        if ($params_verify) {

            $entity = new BlackboardNewsModel($id);
            $entity->type = $type;
            $entity->title = $title;
            $entity->start_date = $start_date;
            $oldText = $entity->text;
            $entity->text = $text;
            $entity->end_date = $end_date;
            $updated = $entity->update();

            if ($updated) {
                $this->moveTemporaryImages($entity, $oldText);
            }

            $result
                ->setMessage($updated ? __('news', 'La noticia ha sido actualizada') : __('news', 'No se ha podido actualizar la noticia, intente más tarde.'))
                ->operation('editNew')
                ->setSuccess($updated);

        } else {
            $result
                ->setMessage(__('news', 'Los parámetros recibidos no son correctos'))
                ->operation('editNew');
        }

        return $response->withJson($result);
    }

    /**
     * moveTemporaryImages
     *
     * @param BlackboardNewsModel $entity
     * @param string $oldText
     * @return void
     */
    protected function moveTemporaryImages(BlackboardNewsModel &$entity, string $oldText = null)
    {
        $imagesOnText = [];
        $imagesOnOldText = [];
        $currentImagesOnText = [];

        $isEdit = !is_null($oldText) && strlen($oldText) > 0;
        $id = $entity->id;

        $regex = '/https?\:\/\/[^\",]+/i';

        preg_match_all($regex, $entity->text, $imagesOnText);

        $imagesOnText = $imagesOnText[0];

        if (count($imagesOnText) > 0) {

            foreach ($imagesOnText as $url) {

                if (strpos($url, $this->uploadDirTmpURL) !== false) {

                    $filename = str_replace($this->uploadDirTmpURL, '', $url);

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

                    $entity->text = str_replace($url, $_url, $entity->text);

                    $currentImagesOnText[] = $_url;

                } elseif (strpos($url, $this->uploadDirURL) !== false) {

                    $currentImagesOnText[] = $url;

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
     * getNews
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function getNews(Request $request, Response $response, array $args)
    {
        $page = (int) $request->getQueryParam('page', 1);
        $perPage = (int) $request->getQueryParam('per_page', 10);
        $isList = $request->getQueryParam('is_list', null) === 'true';

        $model = (new BlackboardNewsModel())->getModel();

        $now = (new \DateTime())->format('Y-m-d h:s:i');

        if ($isList) {
            $where = trim(implode(' ', [
                " author = " . $this->user->id . ' OR ',
                " type = " . $this->user->type . ' AND ',
                " (start_date <= '$now' AND end_date > '$now') OR ",
                " (start_date IS NULL AND end_date IS NULL) ",
            ]));
        } else {
            $where = trim(implode(' ', [
                " type = " . $this->user->type . ' AND ',
                " (start_date <= '$now' AND end_date > '$now') OR ",
                " (start_date IS NULL AND end_date IS NULL) ",
            ]));
        }

        $query = $model->select()->where($where)->orderBy('start_date DESC, created_date DESC');

        $queryTotal = clone $query;

        $queryTotal->select()->execute();
        $query->execute(false, $page, $perPage);

        $result = $query->result();
        $total = count($queryTotal->result());

        $filtered = [];

        foreach ($result as $element) {

            $mapper = new BlackboardNewsModel($element->id);
            $element->author = [
                'id' => $mapper->author->id,
                'username' => $mapper->author->username,
                'firstname' => $mapper->author->firstname,
                'secondname' => $mapper->author->secondname,
                'first_lastname' => $mapper->author->first_lastname,
                'second_lastname' => $mapper->author->second_lastname,
                'email' => $mapper->author->email,
                'meta' => $mapper->author->meta,
            ];
            $element->type = [
                'code' => $element->type,
                'label' => UsersModel::getTypesUser()[$element->type],
            ];

            if (!is_null($element->start_date) && !is_null($element->end_date)) {
                $element->start_date = $mapper->start_date->format('d-m-Y h:i A');
                $element->end_date = $mapper->end_date->format('d-m-Y h:i A');
            }

            $element->title = $mapper->title;
            $element->text = $mapper->text;

            $filtered[] = $element;

        }

        return $response->withJson(
            (new ResultOperations([], 'Noticias', __('news', 'Paginado de noticias')))
                ->setValue('news', $filtered)
                ->setValue('page', $page)
                ->setValue('perPage', $perPage)
                ->setValue('total', $total)
                ->setValue('pages', ceil($total / $perPage))
        );
    }

    /**
     * imageHandler
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function imageHandler(Request $request, Response $response, array $args)
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

                    $url = append_to_url($this->uploadDirTmpURL, $filename);

                    if (!is_null($filename)) {
                        $result
                            ->operation('uploadImage')
                            ->setMessage(__('news', 'Imagen subida'))
                            ->setSuccess(true);
                        $result->setValue('path', $url);
                    } else {
                        $result
                            ->operation('uploadImage')
                            ->setMessage(__('news', 'La imagen no pudo ser subida, intente después.'));
                    }

                }

            }
        } else {
            $result
                ->operation('uploadImage')
                ->setMessage(__('news', 'No se ha subido ninguna imagen.'));
        }

        return $response->withJson($result);
    }

    /**
     * routes
     *
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $groupSegmentURL = $group->getGroupSegment();
        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';
        $classname = BlackboardNewsController::class;
        $routes = [];
        $all_roles = array_keys(UsersModel::TYPES_USERS);
        $edition_permissions = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN,
        ];

        //──── GET ─────────────────────────────────────────────────────────────────────────
        $routes[] = new Route(
            "{$startRoute}create[/]",
            $classname . ':writeForm',
            'blackboard-news-create-form',
            'GET',
            true,
            null,
            $edition_permissions
        );
        $routes[] = new Route(
            "{$startRoute}edit/{id}[/]",
            $classname . ':editForm',
            'blackboard-news-edit-form',
            'GET',
            true,
            null,
            $edition_permissions
        );
        $routes[] = new Route(
            "{$startRoute}news[/]",
            $classname . ':getNews',
            'blackboard-news-get',
            'GET',
            true,
            null,
            $all_roles
        );
        $routes[] = new Route(
            "{$startRoute}list[/]",
            $classname . ':listView',
            'blackboard-news-list',
            'GET',
            true,
            null,
            $edition_permissions
        );

        //──── POST ─────────────────────────────────────────────────────────────────────────
        $routes[] = new Route(
            "{$startRoute}create[/]",
            $classname . ':registerNew',
            'blackboard-news-create',
            'POST',
            true,
            null,
            $edition_permissions
        );
        $routes[] = new Route(
            "{$startRoute}edit[/]",
            $classname . ':editNew',
            'blackboard-news-edit',
            'POST',
            true,
            null,
            $edition_permissions
        );
        $routes[] = new Route(
            "{$startRoute}delete/{id}[/]",
            $classname . ':deleteNew',
            'blackboard-news-delete',
            'POST',
            true,
            null,
            $edition_permissions
        );
        $routes[] = new Route(
            "{$startRoute}image-handler[/]",
            $classname . ':imageHandler',
            'blackboard-image-handler',
            'POST',
            true,
            null,
            $edition_permissions
        );

        $group->active(BLACKBOARD_NEWS_ENABLED);
        $group->register($routes);

        return $group;
    }
}
