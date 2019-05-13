<?php
/**
 * ArticleController.php
 */

namespace PiecesPHP\BuiltIn\Article\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use PiecesPHP\BuiltIn\Article\Mappers\ArticleMapper;
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
use PiecesPHP\BuiltIn\Article\Category\Controllers\CategoryController;
use PiecesPHP\BuiltIn\Article\Category\Mappers\CategoryMapper;

/**
 * ArticleController.
 *
 * @package     PiecesPHP\BuiltIn\Article\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class ArticleController extends AdminPanelController
{

	const UPLOAD_DIR = 'piecesphp/articles';
	const UPLOAD_DIR_TMP = 'piecesphp/articles/tmp';
	const FORMAT_DATETIME = 'd-m-Y h:i A';

	/**
	 * $prefixParentEntity
	 *
	 * @var string
	 */
	protected static $prefixParentEntity = 'piecesphp-built-in';
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

		$this->model = (new ArticleMapper)->getModel();
		set_title(self::$title . ' - ' . get_title());

		$this->uploadDir = append_to_url(get_config('upload_dir'), self::UPLOAD_DIR);
		$this->uploadTmpDir = append_to_url(get_config('upload_dir'), self::UPLOAD_DIR_TMP);
		$this->uploadDirURL = append_to_url(get_config('upload_dir_url'), self::UPLOAD_DIR);
		$this->uploadDirTmpURL = append_to_url(get_config('upload_dir_url'), self::UPLOAD_DIR_TMP);
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

		$action = self::routeName('actions-add');
		$back_link = self::routeName('list');
		$options_categories = array_to_html_options(CategoryMapper::allForSelect(), null);

		$data = [];
		$data['action'] = $action;
		$data['back_link'] = $back_link;
		$data['options_categories'] = $options_categories;
		$data['title'] = self::$title;

		import_quilljs(['imageUpload', 'imageResize']);

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

		$id = $request->getAttribute('id', null);
		$id = !is_null($id) && ctype_digit($id) ? (int)$id : null;

		$element = new ArticleMapper($id);

		if (!is_null($element->id)) {

			$action = self::routeName('actions-edit');
			$back_link = self::routeName('list');
			$options_categories = array_to_html_options(CategoryMapper::allForSelect(), $element->category->id);

			$data = [];
			$data['action'] = $action;
			$data['element'] = $element;
			$data['back_link'] = $back_link;
			$data['options_categories'] = $options_categories;
			$data['title'] = self::$title;

			import_quilljs(['imageUpload', 'imageResize']);

			$this->render('panel/layout/header');
			$this->render('panel/' . self::$prefixParentEntity . '/' . self::$prefixSingularEntity . '/edit-form', $data);
			$this->render('panel/layout/footer');
		} else {
			throw new NotFoundException($request, $response);
		}
	}

	/**
	 * list
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param array $args
	 * @return void
	 */
	public function list(Request $request, Response $response, array $args)
	{

		$process_table = self::routeName('datatables');
		//$back_link = self::routeName();
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
	 * articles
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param array $args
	 * @return Response
	 */
	public function articles(Request $request, Response $response, array $args)
	{

		if ($request->isXhr()) {

			$query = $this->model->select();

			$query->execute();

			return $response->withJson($query->result());
		} else {
			throw new NotFoundException($request, $response);
		}
	}

	/**
	 * articlesByCategory
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param array $args
	 * @return Response
	 */
	public function articlesByCategory(Request $request, Response $response, array $args)
	{

		if ($request->isXhr() ||  true) {

			$type = $request->getQueryParam('type', 'friendly_url');
			$category_value = $request->getAttribute('category', null);
			$exists = false;
			$category = null;

			if ($type == 'friendly_url') {
				$exists = CategoryMapper::existsByFriendlyURL((string)$category_value);
			} elseif ($type == 'id') {
				$category_value = !is_null($category_value) && ctype_digit($category_value) ? $category_value : -1;
				$exists = CategoryMapper::existsByID((int)$category_value);
			} elseif ($type == 'name') {
				$exists = CategoryMapper::existsByName((string)$category_value);
			}

			if ($exists) {
				$category = CategoryMapper::getBy($category_value, $type);
			}

			if ($category !== null) {
				return $response->withJson(ArticleMapper::allByCategory((int)$category->id));
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

		if ($request->isXhr() ||  true) {

			$type = $request->getQueryParam('type', 'friendly_url');
			$article_value = $request->getAttribute('article', null);
			$exists = false;
			$article = null;

			if ($type == 'friendly_url') {
				$exists = ArticleMapper::existsByFriendlyURL($article_value);
			} elseif ($type == 'id') {
				$article_value = !is_null($article_value) && ctype_digit($article_value) ? $article_value : -1;
				$exists = ArticleMapper::existsByID((int)$article_value);
			}

			if ($exists) {
				$article = ArticleMapper::getBy($article_value, $type);
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
	 * articlesDataTables
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param array $args
	 * @return Response
	 */
	public function articlesDataTables(Request $request, Response $response, array $args)
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
			];

			$result = DataTablesHelper::process([
				'columns_order' => $columns_order,
				'mapper' => new ArticleMapper(),
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

					$mapper = new ArticleMapper($e->id);

					return [
						$mapper->id,
						strlen($mapper->title) > 50 ? substr($mapper->title, 0, 50) . '...' : $mapper->title,
						$mapper->author->username,
						$mapper->category->name,
						!is_null($mapper->start_date) ? $mapper->start_date->format('d-m-Y h:i:s') : '-',
						!is_null($mapper->end_date) ? $mapper->end_date->format('d-m-Y h:i:s') : '-',
						$mapper->created->format('d-m-Y h:i:s'),
						!is_null($mapper->updated) ? $mapper->updated->format('d-m-Y h:i:s') : 'Nunca',
						(string)$buttonEdit,
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
			$friendly_url = ArticleMapper::generateFriendlyURL($title, $id);
			$content = clean_string($content);

			$is_duplicate = ArticleMapper::isDuplicate($title, $friendly_url, (int)$category, $id);

			if (!$is_duplicate) {

				if (!$is_edit) {

					$mapper = new ArticleMapper();

					try {

						$mapper->category = $category;
						$mapper->title = $title;
						$mapper->friendly_url = $friendly_url;
						$mapper->content = $content;
						$mapper->start_date = $start_date;
						$mapper->end_date = $end_date;
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
					}
				} else {

					$mapper = new ArticleMapper((int)$id);
					$exists = !is_null($mapper->id);

					if ($exists) {

						try {

							$oldText = $mapper->content;
							$mapper->category = $category;
							$mapper->title = $title;
							$mapper->friendly_url = $friendly_url;
							$mapper->content = $content;
							$mapper->start_date = $start_date;
							$mapper->end_date = $end_date;
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

					$url = append_to_url($this->uploadDirTmpURL, $filename);

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
	 * @param ArticleMapper $entity
	 * @param string $oldText
	 * @return void
	 */
	protected function moveTemporaryImages(ArticleMapper &$entity, string $oldText = null)
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

					$entity->content = str_replace($url, $_url, $entity->content);

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
	 * routeName
	 *
	 * @param string $name
	 * @param array $params
	 * @param bool $silentOnNotExists
	 * @return string
	 */
	protected static function routeName(string $name = null, array $params = [], bool $silentOnNotExists = false)
	{
		if (!is_null($name)) {
			$name = trim($name);
			$name = strlen($name) > 0 ? "-{$name}" : '';
		}

		$name = !is_null($name) ? self::$prefixParentEntity . '-' . self::$prefixEntity . $name : self::$prefixParentEntity;

		$allowed = false;
		$current_user = get_config('current_user');

		if ($current_user != false) {
			$allowed = Roles::hasPermissions($name, (int)$current_user->type);
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

			$permisos_estados_gestion = [
				UsersModel::TYPE_USER_ROOT,
				UsersModel::TYPE_USER_ADMIN,
			];

			$group->active(PIECES_PHP_BLOG_ENABLED);
			$group->register($routes);

			//Rutas básicas
			$group->register(
				self::genericManageRoutes($startRoute, self::$prefixParentEntity, self::class, self::$prefixEntity, $permisos_estados_gestion, true)
			);

			//Otras rutas
			$namePrefix = self::$prefixParentEntity . '-' . self::$prefixEntity;
			$startRoute .= self::$prefixEntity;
			$group->register([
				new Route(
					"{$startRoute}/list/{category}",
					self::class . ":articlesByCategory",
					"{$namePrefix}-ajax-all-category",
					'GET'
				),
			]);
			$group->register([
				new Route(
					"{$startRoute}/single/{article}",
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

		$routes = [
			new Route(
				"{$startRoute}",
				"{$handler}:{$uriPrefix}",
				"{$namePrefix}-ajax-all",
				'GET'
			),
			new Route(
				"{$startRoute}/datatables[/]",
				"{$handler}:{$uriPrefix}DataTables",
				"{$namePrefix}-datatables",
				'GET'
			),
			new Route(
				"{$startRoute}/list[/]",
				"{$handler}:list",
				"{$namePrefix}-list",
				'GET'
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
				'POST'
			);
		}

		return $routes;
	}
}
