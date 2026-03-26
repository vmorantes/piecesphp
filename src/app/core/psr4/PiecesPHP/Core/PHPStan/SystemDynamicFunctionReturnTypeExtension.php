<?php
/**
 * SystemDynamicFunctionReturnTypeExtension.php
 */

namespace PiecesPHP\Core\PHPStan;

use App\Model\UsersModel;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use stdClass;

/**
 * SystemDynamicFunctionReturnTypeExtension.
 *
 * Punto centralizado para resolver tipos dinámicos del sistema.
 *
 * @package     PiecesPHP\Core\PHPStan
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SystemDynamicFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

    public function isFunctionSupported(FunctionReflection $function): bool
    {
        return isset($this->functionResolvers()[$function->getName()]);
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope
    ): Type {
        $name = $functionReflection->getName();

        $resolver = $this->functionResolvers()[$name] ?? null;

        if ($resolver === null) {
            return new MixedType();
        }

        return $resolver($functionCall, $scope);
    }

    /**
     * Registro de resolvers de funciones
     */
    private function functionResolvers(): array
    {
        return [
            'get_config' => fn(FuncCall $call, Scope $scope) => $this->resolveGetConfig($call, $scope),
        ];
    }

    /* ============================================================
     * RESOLVERS
     * ============================================================
     */

    /**
     * Resolver para get_config
     */
    private function resolveGetConfig(FuncCall $functionCall, Scope $scope): Type
    {
        if (!isset($functionCall->args[0])) {
            return new MixedType();
        }

        $argType = $scope->getType($functionCall->args[0]->value);

        if (!$argType instanceof ConstantStringType) {
            return new MixedType();
        }

        $name = $argType->getValue();

        $map = [
            '_routes_' => new ArrayType(
                new StringType(),
                new ArrayType(new MixedType(), new MixedType())
            ),
            'additional_langs_to_scan' => new ArrayType(new IntegerType(), new StringType()),
            'admin_url' => $this->buildArray([
                'relative' => new BooleanType(),
                'url' => new StringType(),
            ]),
            'allowed_langs' => new ArrayType(new IntegerType(), new StringType()),
            'alternatives_url' => new ArrayType(new StringType(), new StringType()),
            'alternatives_url_include_current' => new ArrayType(new StringType(), new StringType()),
            'app_lang' => new StringType(),
            'as_modules_assets' => new ArrayType(new IntegerType(), new StringType()),
            'Azure' => $this->buildArray([
                'BASE_STORAGE_ACCOUNT_NAME' => new StringType(),
                'BASE_STORAGE_ACCOUNT_KEY' => new StringType(),
                'BASE_STORAGE_ACCESS_QUERY_PARAMS' => new StringType(),
                'SPEECH_SUBSCRIPTION_KEY' => new StringType(),
                'SPEECH_REGION' => new StringType(),
            ]),
            'backgoundProblems' => new StringType(),
            'backgrounds' => new ArrayType(new IntegerType(), new StringType()),
            'bg_tools_buttons' => new StringType(),
            'body_gradient' => new StringType(),
            'BuiltInBannerConfiguration' => new ArrayType(
                new StringType(),
                new ArrayType(
                    new StringType(),
                    new UnionType([
                        new StringType(),
                        new IntegerType(),
                        new BooleanType(),
                    ])
                )
            ),
            'cache_stamp_render_files' => new BooleanType(),
            'cacheStamp' => new StringType(),
            'check_aud_on_auth' => new BooleanType(),
            'control_access_login' => new BooleanType(),
            'cookie_lang_definer' => new StringType(),
            'cookies' => $this->buildArray([
                'lifetime' => new IntegerType(),
                'path' => new StringType(),
                'domain' => new StringType(),
                'secure' => new BooleanType(),
                'httponly' => new BooleanType(),
            ]),
            'CronJobKey' => new StringType(),
            'current_user' => new UnionType([
                new ObjectType(UsersModel::class),
                new ObjectType(stdClass::class),
            ]),
            //'custom_assets' => new StringType(),
            //'default_assets' => new StringType(),
            'default_lang' => new StringType(),
            'description' => new StringType(),
            //'DYNAMIC_TRANSLATIONS' => new StringType(),
            //'errorMiddleware' => new StringType(),
            //'extra_scripts' => new StringType(),
            'favicon' => new StringType(),
            'favicon-back' => new StringType(),
            //'flushing_pcsphp' => new StringType(),
            'font_color_one' => new StringType(),
            'font_color_two' => new StringType(),
            'font_family_global' => new StringType(),
            'font_family_sidebars' => new StringType(),
            //'format_date_lang_sql' => new StringType(),
            //'front_configurations' => new StringType(),
            //'get_fomantic_flag_by_lang' => new StringType(),
            //'get_locale_versions_by_locale' => new StringType(),
            //'global_assets' => new StringType(),
            //'global_requireds_assets' => new StringType(),
            'GroqAPIKey' => new StringType(),
            //'imported_assets' => new StringType(),
            //'keywords' => new StringType(),
            'lang_by_browser' => new BooleanType(),
            'lang_by_cookie' => new BooleanType(),
            'lang_by_url' => new BooleanType(),
            //'lc_time_names_mysql' => new StringType(),
            'lock_assets' => new BooleanType(),
            'logo' => new StringType(),
            //'mail' => new StringType(),
            'mailing_logo' => new StringType(),
            //'mailjet' => new StringType(),
            'main_brand_color' => new StringType(),
            'menu_color_background' => new StringType(),
            'menu_color_font' => new StringType(),
            'menu_color_mark' => new StringType(),
            //'menus' => new StringType(),
            'meta_theme_color' => new StringType(),
            'MistralAIApiKey' => new StringType(),
            'modelMistral' => new StringType(),
            'modelOpenAI' => new StringType(),
            //'no_scan_lang_groups' => new StringType(),
            //'no_scan_langs' => new StringType(),
            'open_graph_image' => new StringType(),
            'OpenAIApiKey' => new StringType(),
            'osTicketAPI' => new StringType(),
            'osTicketAPIKey' => new StringType(),
            'owner' => new StringType(),
            'partners' => new StringType(),
            'partnersVertical' => new StringType(),
            //'pcsphp_system_translations' => new StringType(),
            'prefix_lang' => new StringType(),
            //'responseExpectedLang' => new StringType(),
            'roles' => $this->buildArray([
                'active' => new BooleanType(),
                'baseInitialSegmentedPermissions' => $this->buildArray([
                    'generals' => new ArrayType(new IntegerType(), new StringType()),
                    'administratives' => new ArrayType(new IntegerType(), new StringType()),
                    'superiors' => new ArrayType(new IntegerType(), new StringType()),
                ]),
                'types' => new ArrayType(new IntegerType(), $this->buildArray([
                    'code' => new IntegerType(),
                    'name' => new StringType(),
                    'all' => new BooleanType(),
                    'allowed_routes' => new ArrayType(new IntegerType(), new StringType()),
                ])),
            ]),
            'second_brand_color' => new StringType(),
            //'slim_app' => new StringType(),
            'slim_container' => new ObjectType(\PiecesPHP\Core\Routing\DependenciesInjector::class),
            'statics_path' => new StringType(),
            'terminal_color' => new UnionType([
                new StringType(),
                new NullType(),
            ]),
            'terminal_format_options' => new UnionType([
                new ArrayType(new MixedType(), new MixedType()),
                new NullType(),
            ]),
            //'terminalData' => new StringType(),
            //'terminalTaskAvailablesVerbose' => new StringType(),
            'title' => new StringType(),
            'title_app' => new StringType(),
            //'translationAI' => new StringType(),
            //'translationAIEnable' => new StringType(),
            'upload_dir' => new StringType(),
            'upload_dir_url' => new StringType(),
        ];

        return $map[$name] ?? new MixedType();
    }

    /* ============================================================
     * HELPERS
     * ============================================================
     */

    private function buildArray(array $fields): Type
    {
        $builder = ConstantArrayTypeBuilder::createEmpty();

        foreach ($fields as $key => $type) {
            $builder->setOffsetValueType(
                new ConstantStringType($key),
                $type
            );
        }

        return $builder->getArray();
    }
}
