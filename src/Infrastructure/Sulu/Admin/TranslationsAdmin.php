<?php

declare(strict_types=1);

namespace Phpro\SuluTranslationsBundle\Infrastructure\Sulu\Admin;

use Phpro\SuluTranslationsBundle\Presentation\Controller\Admin\ListController;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

final class TranslationsAdmin extends Admin
{
    final public const string SECURITY_CONTEXT = 'phpro_translations';
    private const string SECURITY_CONTEXT_GROUP = 'Settings';
    final public const string LIST_KEY = 'phpro_translations_list';
    private const string FORM_KEY = 'phpro_translations_form';
    private const string LIST_VIEW = 'phpro_translations_list_view';
    private const string EDIT_FORM_VIEW = 'phpro_translations_form_view';

    public function __construct(
        private readonly ViewBuilderFactoryInterface $viewBuilderFactory,
        private readonly SecurityCheckerInterface $securityChecker,
    ) {
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if (!$this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            return;
        }

        $navigationItem = new NavigationItem('Manage translations');
        $navigationItem->setPosition(100);
        $navigationItem->setView(self::LIST_VIEW);
        $navigationItemCollection->get(Admin::SETTINGS_NAVIGATION_ITEM)->addChild($navigationItem);
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        if (!$this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::VIEW)) {
            return;
        }

        $listToolbarActions = $editFormToolbarActions = [];
        if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $editFormToolbarActions[] = new ToolbarAction('sulu_admin.save');
            $listToolbarActions[] = new ToolbarAction('phpro_translation.export_translations');
        }

        // Configure List View
        $listView = $this->viewBuilderFactory->createListViewBuilder(self::LIST_VIEW, '/translations')
            ->setResourceKey(ListController::RESOURCE_KEY)
            ->setListKey(self::LIST_KEY)
            ->setTitle('Translations')
            ->addListAdapters(['table'])
            ->disableSelection()
            ->setEditView(self::EDIT_FORM_VIEW)
            ->addToolbarActions($listToolbarActions);
        $viewCollection->add($listView);

        // Configure Edit View
        $editFormView = $this->viewBuilderFactory->createResourceTabViewBuilder(
            self::EDIT_FORM_VIEW,
            '/translations/:id'
        )
            ->setResourceKey(ListController::RESOURCE_KEY)
            ->setBackView(self::LIST_VIEW)
            ->setTitleProperty('title');
        $viewCollection->add($editFormView);

        $editDetailsFormView = $this->viewBuilderFactory->createFormViewBuilder(
            self::EDIT_FORM_VIEW.'.details',
            '/details'
        )
            ->setResourceKey(ListController::RESOURCE_KEY)
            ->setFormKey(self::FORM_KEY)
            ->setTabTitle('sulu_admin.details')
            ->addToolbarActions($editFormToolbarActions)
            ->setParent(self::EDIT_FORM_VIEW);
        $viewCollection->add($editDetailsFormView);
    }

    public function getSecurityContexts(): array
    {
        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                self::SECURITY_CONTEXT_GROUP => [
                    self::SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                ],
            ],
        ];
    }
}
