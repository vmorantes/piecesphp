# Abreviaturas
- MV = Movido a (plural MVMV)
- RN = Renombrado a
- P = Property (plural PP)
- M = Method (plural MM)
- F = Function (plural FF)
- D = Borrado (plural DD)
- A = Agregado (plural AA)
- U = Ruta (plural UU)

# v. 2.9

## Generales
- src/composer.json
	- **D** Tarea "git-log" de scripts
- **D** Plugin isotope
- **D** Plugin alertify
- **D** Plugin handlebars

## PHP
- src/app/controller/PublicAreaController.php
	- **A** *P*: private static $automaticImports
	- **D** izitoast
	- Importaciones opcionales con respecto a $automaticImports

- src/app/core/system-controllers/Test.php
	- **D** *MM* index y overviewBack

- src/app/config/routes.php
	- **D** *U* overview-2 de Test

- **D** src/app/core/system-views/layout
- **D** src/app/core/system-views/layout/header.php
- **D** src/app/core/system-views/layout/footer.php
- **D** src/app/core/system-views/pages
- **D** src/app/core/system-views/pages/test
- **D** src/app/core/system-views/pages/test/overview.php

## JS

- src/statics/core/js/helpers.js
	- **D** *F* templateResolver
	- **D** *F* filterSorterResolver
	- **D** *F* formatHTML
	- **D** *F* quillsHandlers (**MV** configurations.js dentro de configRichEditor)
	- **D** *F* simulateInputNumberFormat
	- **D** *F* pcsFormatNumberString
	- **D** *F* cropperToDataURL
	- **D** *F* pcsTopBar
	- **D** *F* pcsSideBar (**MV** configurations.js como pcsAdminSideBar)

- src/statics/core/js/configurations.js
	- **A** *F* _i18n
	- **A** *F* pcsAdminSideBar
	- **A** *F* quillsHandlers dentro de configRichEditor
	- **D** *F* dataTableServerProccesing (**MV** helpers.js)
	- **D** *F* genericFormHandler (**MV** helpers.js)
	- **D** *F* showGenericLoader (**MV** helpers.js)
	- **D** *F* removeGenericLoader (**MV** helpers.js)

- **D** src/statics/core/js/internacionalizacion.js

- src/statics/core/main_system_user.js **MV** src/statics/core/js/user-system/main_system_user.js
- src/statics/core/PiecesPHPSystemUserHelper.js **MV** src/statics/core/js/user-system/PiecesPHPSystemUserHelper.js
- src/statics/core/sustem.users.jquery.js **MV** src/statics/core/js/user-system/sustem.users.jquery.js





