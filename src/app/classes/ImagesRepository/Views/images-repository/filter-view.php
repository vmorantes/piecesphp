<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use ImagesRepository\Controllers\ImagesRepositoryController;
/**
 * @var ImagesRepositoryController $this
 */;
/**
 * @var string $langGroup
 */;
$langGroup;
$langGroupDatatables = 'datatables';

$appendParamToURL = function (string $url, string $paramName, string $paramValue) {

    $parsedURL = parse_url($url);
    $hasParams = isset($parsedURL['query']) ? !empty(explode('&', $parsedURL['query'])) : null;
    $queryTokenIndex = strpos($url, '?');
    $hasQueryToken = $queryTokenIndex !== false;
    $querySegment = $hasQueryToken ? substr($url, $queryTokenIndex) : null;
    if ($hasParams && last_char($querySegment) == '&') {
        $hasParams = false;
    }

    $param = $hasParams ? '&' : (!$hasQueryToken ? '?' : '');
    $param .= "{$paramName}={$paramValue}";
    return $url . $param;
};

$yearsItems = '';
foreach($years as $yearFilter){
    $yearURL = ($appendParamToURL)($filterURLNoYear, 'year', $yearFilter);
    $yearsItems .= "<a href='{$yearURL}' class='item'>{$yearFilter}</a>";
}

$validateParam = function ($e) {
    return is_scalar($e) && is_string($e) && mb_strlen(trim($e)) > 0;
};
$params = [
    __($langGroup, 'Año') => $year,
];
$params = array_filter($params, function ($e) use($validateParam){
    return ($validateParam)($e);
});
$hasParams = !empty($params) || ($validateParam)($search);

?>

<div class="container-standard-sidebar-content">

    <div class="sidebar">

        <div class="title"><?= __($langGroup, 'Fotografías'); ?></div>

        <div class="content">

            <div class="ui form">

                <form class="field" method="GET" action="<?= $filterURL; ?>">
                    <input type="hidden" name="year" value="<?= $year; ?>">
                    <div class="ui icon input">
                        <input type="text" name="searchText" placeholder="<?= __($langGroup, 'Buscar...'); ?>" value="<?= $search; ?>">
                    </div>
                </form>

                <?php if($hasParams): ?>
                <div class="field">
                    <a href="<?= $filterURLWhitoutParams; ?>" class="ui fluid button grey labeled icon">
                        <i class="icon eraser"></i>
                        <?= __($langGroup, 'Limpiar'); ?>
                    </a>
                </div>
                <?php endif; ?>

                <div class="fields items-dropdowns">

                    <div class="field">

                        <div class="ui accordion">

                            <div class="title">
                                <i class="dropdown icon"></i>
                                <?= __($langGroup, 'Años'); ?>
                            </div>

                            <div class="content">

                                <div class="transition hidden ui list">
                                    <a href="<?= $filterURLNoYear; ?>" class="item"><?= __($langGroup, 'Todos'); ?></a>
                                    <?= $yearsItems; ?>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <?php if(!empty($params)): ?>
                <div class="field">
                    <strong><?= __($langGroup, 'Filtrando por:'); ?></strong>
                    <div class="ui horizontal list">
                        <?php foreach($params as $paramDisplay => $paramValue): ?>
                        <div class="item">
                            <i class="filter icon"></i>
                            <div class="content">
                                <?= $paramDisplay; ?>: <?= $paramValue; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>

        </div>

    </div>

    <div class="content">

        <div class="header-list">

            <div>

                <a href="<?=$backLink;?>" class="ui labeled icon button">
                    <i class="icon left arrow"></i>
                    <?=__($langGroup, 'Regresar');?>
                </a>

            </div>

            <br><br>

            <h3 class="title-list small">
                <?=$title;?>
            </h3>

        </div>

        <br>

        <div class="container-cards-standard-list">

            <div class="table-to-cards">

                <div class="ui form component-controls">

                    <div class="fields">

                        <div class="field">

                            <label><?= __($langGroupDatatables, 'Resultados visibles') ?></label>
                            <input type="number" length-pagination placeholder="10">

                        </div>

                        <div class="field">

                            <label><?= __($langGroupDatatables, 'Ordenar por') ?>:</label>
                            <select class="ui dropdown" options-order></select>

                        </div>

                        <div class="field">

                            <label>&nbsp;</label>
                            <select class="ui dropdown" options-order-type>
                                <option selected value="ASC"><?= __($langGroupDatatables, 'ASC') ?></option>
                                <option value="DESC"><?= __($langGroupDatatables, 'DESC') ?></option>
                            </select>

                        </div>

                    </div>

                </div>

                <table url="<?= $processTableLink; ?>" style='display:none;'>

                    <thead>

                        <tr>
                            <th><?=__($langGroup, 'N°');?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

    </div>

</div>
