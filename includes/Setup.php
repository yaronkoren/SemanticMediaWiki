<?php

namespace SMW;

/**
 * Extension setup and registration
 *
 * Register all hooks, set up extension credits etc.
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
final class Setup implements ContextAware {

	/** @var array */
	protected $globals;

	/** @var Settings */
	protected $settings;

	/** @var ContextResource */
	protected $context = null;

	/**
	 * @since 1.9
	 *
	 * @param array &$globals
	 * @param ContextResource|null $context
	 */
	public function __construct( &$globals, ContextResource $context = null ) {
		$this->globals =& $globals;
		$this->context = $context;
	}

	/**
	 * Initialisation of the extension
	 *
	 * @since 1.9
	 */
	public function run() {
		Profiler::In();

		$this->init();

		$this->loadSettings();

		// Register messages files
		$this->registerMessageFiles();

		// Register Api modules
		$this->registerApiModules();

		// Register Job classes
		$this->registerJobClasses();

		// Register Special pages
		$this->registerSpecialPages();

		// Rights and groups
		$this->registerRights();

		// ParamDefinitions
		$this->registerParamDefinitions();

		//FooterIcons
		$this->registerFooterIcon();

		// Register hooks (needs to be loaded after settings are initialized)
		$this->registerFunctionHooks();

		// Register parser hooks
		$this->registerParserHooks();

		Profiler::Out();
	}

	/**
	 * Init some globals that are not part of the configuration settings
	 *
	 * @since 1.9
	 */
	protected function init() {

		$this->globals['smwgMasterStore'] = null;
		$this->globals['smwgIQRunningNumber'] = 0;

	}

	/**
	 * @see ContextAware::withContext
	 *
	 * @since 1.9
	 *
	 * @return ContextResource
	 */
	public function withContext() {

		if ( $this->context === null ) {
			$this->context = new BaseContext();
		}

		return $this->context;
	}

	/**
	 * Load Semantic MediaWiki specific settings
	 *
	 * @since 1.9
	 */
	protected function loadSettings() {
		$this->settings = $this->registerSettings( Settings::newFromGlobals( $this->globals ) );
	}

	/**
	 * Register settings
	 *
	 * @since 1.9
	 */
	protected function registerSettings( Settings $settings ) {
		$this->withContext()->getDependencyBuilder()->getContainer()->registerObject( 'Settings', $settings );
		return $settings;
	}

	/**
	 * Register messages files
	 *
	 * @since 1.9
	 */
	protected function registerMessageFiles() {

		$smwgIP = $this->settings->get( 'smwgIP' );

		$this->globals['wgExtensionMessagesFiles']['SemanticMediaWiki'] = $smwgIP . 'languages/SMW_Messages.php';
		$this->globals['wgExtensionMessagesFiles']['SemanticMediaWikiAlias'] = $smwgIP . 'languages/SMW_Aliases.php';
		$this->globals['wgExtensionMessagesFiles']['SemanticMediaWikiMagic'] = $smwgIP . 'languages/SMW_Magic.php';

	}

	/**
	 * Register Api modules
	 *
	 * @note Associative array mapping module name to class name
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:$wgAPIModules
	 *
	 * @since 1.9
	 */
	protected function registerApiModules() {

		$this->globals['wgAPIModules']['smwinfo'] = '\SMW\ApiInfo';
		$this->globals['wgAPIModules']['ask']     = '\SMW\ApiAsk';
		$this->globals['wgAPIModules']['askargs'] = '\SMW\ApiAskArgs';
		$this->globals['wgAPIModules']['browse']  = '\SMW\ApiBrowse';

	}

	/**
	 * Register Job classes to their handling classes
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:$wgJobClasses
	 *
	 * @since 1.9
	 */
	protected function registerJobClasses() {

		$this->globals['wgJobClasses']['SMW\UpdateJob']           = 'SMW\UpdateJob';
		$this->globals['wgJobClasses']['SMWRefreshJob']           = 'SMWRefreshJob';
		$this->globals['wgJobClasses']['SMW\UpdateDispatcherJob'] = 'SMW\UpdateDispatcherJob';

	}

	/**
	 * Register rights and groups
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:$wgAvailableRights
	 * @see https://www.mediawiki.org/wiki/Manual:$wgGroupPermissions
	 *
	 * @since 1.9
	 */
	protected function registerRights() {

		// Rights
		$this->globals['wgAvailableRights'][] = 'smw-admin';

		// User group rights
		$this->globals['wgGroupPermissions']['sysop']['smw-admin'] = true;
		$this->globals['wgGroupPermissions']['smwadministrator']['smw-admin'] = true;

	}

	/**
	 * Register special pages
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:$wgSpecialPages
	 *
	 * @since 1.9
	 */
	protected function registerSpecialPages() {

		$specials = array(
			'Ask' => array(
				'page' => 'SMWAskPage',
				'group' => 'smw_group'
			),
			'Browse' => array(
				'page' =>  'SMWSpecialBrowse',
				'group' => 'smw_group'
			),
			'PageProperty' => array(
				'page' =>  'SMWPageProperty',
				'group' => 'smw_group'
			),
			'SearchByProperty' => array(
				'page' => 'SMWSearchByProperty',
				'group' => 'smw_group'
			),
			'SMWAdmin' => array(
				'page' => 'SMWAdmin',
				'group' => 'smw_group'
			),
			'SemanticStatistics' => array(
				'page' => 'SMW\SpecialSemanticStatistics',
				'group' => 'wiki'
			),
			'Concepts' => array(
				'page' => 'SMW\SpecialConcepts',
				'group' => 'pages'
			),
			'ExportRDF' => array(
				'page' => 'SMWSpecialOWLExport',
				'group' => 'smw_group'
			),
			'Types' => array(
				'page' => 'SMWSpecialTypes',
				'group' => 'pages'
			),
			'URIResolver' => array(
				'page' => 'SMWURIResolver'
			),
			'Properties' => array(
				'page' => 'SMW\SpecialProperties',
				'group' => 'pages'
			),
			'UnusedProperties' => array(
				'page' => 'SMW\SpecialUnusedProperties',
				'group' => 'maintenance'
			),
			'WantedProperties' => array(
				'page' => 'SMW\SpecialWantedProperties',
				'group' => 'maintenance'
			),
		);

		// Register data
		foreach ( $specials as $special => $page ) {
			$this->globals['wgSpecialPages'][$special] = $page['page'];

			if ( isset( $page['group'] ) ) {
				$this->globals['wgSpecialPageGroups'][$special] = $page['group'];
			}
		}

	}

	/**
	 * Register
	 *
	 * @since 1.9
	 */
	protected function registerParamDefinitions() {

		$this->globals['wgParamDefinitions']['smwformat'] = array(
			'definition'=> 'SMWParamFormat',
		);

		$this->globals['wgParamDefinitions']['smwsource'] = array(
			'definition' => 'SMWParamSource',
		);

	}

	/**
	 * Register poweredby footer icon
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:$wgFooterIcons
	 *
	 * @since 1.9
	 */
	protected function registerFooterIcon() {

		$this->globals['wgFooterIcons']['poweredby']['semanticmediawiki'] = array(
			'src' => $this->settings->get( 'smwgScriptPath' ) . '/resources/images/smw_button.png',
			'url' => 'https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki',
			'alt' => 'Powered by Semantic MediaWiki',
		);
	}

	/**
	 * Register function hooks
	 *
	 * @note $this->globals['wgHooks'] contains a list of hooks which specifies for every event an
	 * array of functions to be called.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:$this->globals['wgHooks']
	 *
	 * @since 1.9
	 */
	protected function registerFunctionHooks() {

		$globals  = $this->globals;
		$context  = $this->withContext();
		$registry = $context->getDependencyBuilder()->newObject( 'FunctionHookRegistry' );

		/**
		 * Hook: Called by BaseTemplate when building the toolbox array and
		 * returning it for the skin to output.
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BaseTemplateToolbox
		 *
		 * @since  1.9
		 */
		$this->globals['wgHooks']['BaseTemplateToolbox'][] = function ( $skinTemplate, &$toolbox ) use ( $registry ) {
			return $registry->register( new BaseTemplateToolbox( $skinTemplate, $toolbox ) )->process();
		};

		/**
		 * Hook: Allows extensions to add text after the page content and article
		 * metadata.
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinAfterContent
		 *
		 * @since  1.9
		 */
		$this->globals['wgHooks']['SkinAfterContent'][] = function ( &$data, $skin = null ) use ( $registry ) {
			return $registry->register( new SkinAfterContent( $data, $skin ) )->process();
		};

		/**
		 * Hook: Called after parse, before the HTML is added to the output
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageParserOutput
		 *
		 * @since  1.9
		 */
		$this->globals['wgHooks']['OutputPageParserOutput'][] = function ( &$outputPage, $parserOutput ) use ( $registry ) {
			return $registry->register( new OutputPageParserOutput( $outputPage, $parserOutput ) )->process();
		};

		/**
		 * Hook: Add changes to the output page, e.g. adding of CSS or JavaScript
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
		 *
		 * @since 1.9
		 */
		$this->globals['wgHooks']['BeforePageDisplay'][] = function ( &$outputPage, &$skin ) use ( $registry ) {
			return $registry->register( new BeforePageDisplay( $outputPage, $skin ) )->process();
		};

		/**
		 * Hook: InternalParseBeforeLinks is used to process the expanded wiki
		 * code after <nowiki>, HTML-comments, and templates have been treated.
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/InternalParseBeforeLinks
		 *
		 * @since 1.9
		 */
		$this->globals['wgHooks']['InternalParseBeforeLinks'][] = function ( &$parser, &$text ) use ( $registry ) {
			return $registry->register( new InternalParseBeforeLinks( $parser, $text ) )->process();
		};

		/**
		 * Hook: NewRevisionFromEditComplete called when a revision was inserted
		 * due to an edit
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/NewRevisionFromEditComplete
		 *
		 * @since 1.9
		 */
		$this->globals['wgHooks']['NewRevisionFromEditComplete'][] = function ( $wikiPage, $revision, $baseId, $user ) use ( $registry ) {
			return $registry->register( new NewRevisionFromEditComplete( $wikiPage, $revision, $baseId, $user ) )->process();
		};

		/**
		 * Hook: TitleMoveComplete occurs whenever a request to move an article
		 * is completed
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
		 *
		 * @since 1.9
		 */
		$this->globals['wgHooks']['TitleMoveComplete'][] = function ( &$oldTitle, &$newTitle, &$user, $oldId, $newId ) use ( $registry ) {
			return $registry->register( new TitleMoveComplete( $oldTitle, $newTitle, $user, $oldId, $newId ) )->process();
		};

		/**
		 * Hook: ArticlePurge executes before running "&action=purge"
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticlePurge
		 *
		 * @since 1.9
		 */
		$this->globals['wgHooks']['ArticlePurge'][] = function ( &$wikiPage ) use ( $registry ) {
			return $registry->register( new ArticlePurge( $wikiPage ) )->process();
		};

		/**
		 * Hook: ArticleDelete occurs whenever the software receives a request
		 * to delete an article
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDelete
		 *
		 * @since 1.9
		 */
		$this->globals['wgHooks']['ArticleDelete'][] = function ( &$wikiPage, &$user, &$reason, &$error ) use ( $context ) {
			$context->getStore()->deleteSubject( $wikiPage->getTitle() );
			return true;
		};

		/**
		 * Hook: LinksUpdateConstructed called at the end of LinksUpdate() construction
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateConstructed
		 *
		 * @since 1.9
		 */
		$this->globals['wgHooks']['LinksUpdateConstructed'][] = function ( $linksUpdate ) use ( $registry ) {
			return $registry->register( new LinksUpdateConstructed( $linksUpdate ) )->process();
		};

		/**
		 * Hook: ParserAfterTidy to add some final processing to the fully-rendered page output
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterTidy
		 *
		 * @since 1.9
		 */
		$this->globals['wgHooks']['ParserAfterTidy'][] = function ( &$parser, &$text ) use ( $registry ) {
			return $registry->register( new ParserAfterTidy( $parser, $text ) )->process();
		};

		/**
		 * Hook: Add extra statistic at the end of Special:Statistics
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialStatsAddExtra
		 *
		 * @since 1.9
		 */
		$this->globals['wgHooks']['SpecialStatsAddExtra'][] = function ( &$extraStats ) use ( $registry, $globals ) {
			return $registry->register( new SpecialStatsAddExtra( $extraStats, $globals['wgVersion'], $globals['wgLang'] ) )->process();
		};

		// Old-style registration

		$this->globals['wgHooks']['LoadExtensionSchemaUpdates'][] = 'SMWHooks::onSchemaUpdate';
		$this->globals['wgHooks']['ParserTestTables'][]    = 'SMWHooks::onParserTestTables';
		$this->globals['wgHooks']['AdminLinks'][]          = 'SMWHooks::addToAdminLinks';
		$this->globals['wgHooks']['PageSchemasRegisterHandlers'][] = 'SMWHooks::onPageSchemasRegistration';
		$this->globals['wgHooks']['ArticleFromTitle'][] = 'SMWHooks::onArticleFromTitle';
		$this->globals['wgHooks']['SkinTemplateNavigation'][] = 'SMWHooks::onSkinTemplateNavigation';
		$this->globals['wgHooks']['UnitTestsList'][] = 'SMWHooks::registerUnitTests';
		$this->globals['wgHooks']['ResourceLoaderTestModules'][] = 'SMWHooks::registerQUnitTests';
		$this->globals['wgHooks']['GetPreferences'][] = 'SMWHooks::onGetPreferences';
		$this->globals['wgHooks']['TitleIsAlwaysKnown'][] = 'SMWHooks::onTitleIsAlwaysKnown';
		$this->globals['wgHooks']['BeforeDisplayNoArticleText'][] = 'SMWHooks::onBeforeDisplayNoArticleText';
		$this->globals['wgHooks']['ResourceLoaderGetConfigVars'][] = 'SMWHooks::onResourceLoaderGetConfigVars';
		$this->globals['wgHooks']['ExtensionTypes'][] = 'SMWHooks::addSemanticExtensionType';

	}

	/**
	 * Register parser hooks
	 *
	 * @since 1.9
	 */
	protected function registerParserHooks() {

		$settings = $this->settings;
		$builder  = $this->withContext()->getDependencyBuilder();

		/**
		 * Called when the parser initialises for the first time
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
		 *
		 * @since  1.9
		 */
		$this->globals['wgHooks']['ParserFirstCallInit'][] = function ( \Parser &$parser ) use ( $builder, $settings ) {

			/**
			 * {{#ask}}
			 *
			 * @since  1.9
			 */
			$parser->setFunctionHook( 'ask', function( $parser ) use ( $builder, $settings ) {
				$ask = $builder->newObject( 'AskParserFunction', array( 'Parser' => $parser ) );
				return $settings->get( 'smwgQEnabled' ) ? $ask->parse( func_get_args() ) : $ask->disabled();
			} );

			/**
			 * {{#show}}
			 *
			 * @since  1.9
			 */
			$parser->setFunctionHook( 'show', function( $parser ) use ( $builder, $settings ) {
				$show = $builder->newObject( 'ShowParserFunction', array( 'Parser' => $parser ) );
				return $settings->get( 'smwgQEnabled' ) ? $show->parse( func_get_args() ) : $show->disabled();
			} );

			/**
			 * {{#subobject}}
			 *
			 * @since  1.9
			 */
			$parser->setFunctionHook( 'subobject', function( $parser ) use ( $builder ) {
				$instance = $builder->newObject( 'SubobjectParserFunction', array( 'Parser' => $parser ) );
				return $instance->parse( ParameterFormatterFactory::newFromArray( func_get_args() ) );
			} );

			return true;
		};

		$this->globals['wgHooks']['ParserFirstCallInit'][] = 'SMW\DocumentationParserFunction::staticInit';
		$this->globals['wgHooks']['ParserFirstCallInit'][] = 'SMW\InfoParserFunction::staticInit';
		$this->globals['wgHooks']['ParserFirstCallInit'][] = 'SMWHooks::onParserFirstCallInit';

	}

}
