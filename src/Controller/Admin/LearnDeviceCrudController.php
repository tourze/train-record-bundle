<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\TrainRecordBundle\Entity\LearnDevice;

/**
 * @extends AbstractCrudController<LearnDevice>
 */
#[AdminCrud(
    routePath: '/train-record/learn-device',
    routeName: 'train_record_learn_device',
)]
#[IsGranted(attribute: 'ROLE_ADMIN')]
final class LearnDeviceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LearnDevice::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('学习设备记录')
            ->setEntityLabelInPlural('学习设备记录管理')
            ->setSearchFields(['deviceFingerprint', 'deviceType', 'browser', 'operatingSystem'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('deviceType', '设备类型'))
            ->add(TextFilter::new('browser', '浏览器'))
            ->add(TextFilter::new('operatingSystem', '操作系统'))
            ->add(BooleanFilter::new('isTrusted', '可信设备'))
            ->add(DateTimeFilter::new('firstUseTime', '首次使用时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnDetail();

        yield AssociationField::new('learnSessions', '学习会话');

        yield TextField::new('deviceFingerprint', '设备指纹')
            ->setRequired(true)
            ->setMaxLength(128)
            ->setHelp('设备唯一标识符')
        ;

        yield TextField::new('deviceType', '设备类型')
            ->setMaxLength(50)
            ->setHelp('如：desktop, mobile, tablet')
        ;

        yield TextField::new('browser', '浏览器信息')
            ->setMaxLength(100)
        ;

        yield TextField::new('operatingSystem', '操作系统')
            ->setMaxLength(100)
        ;

        yield TextField::new('screenResolution', '屏幕分辨率')
            ->setMaxLength(20)
            ->hideOnIndex()
        ;

        yield BooleanField::new('isTrusted', '可信设备')
            ->renderAsSwitch(false)
            ->setHelp('是否为认证的可信设备')
        ;

        yield DateTimeField::new('firstUseTime', '首次使用时间');

        yield DateTimeField::new('lastUseTime', '最后使用时间')
            ->hideOnIndex()
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->hideOnIndex()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }
}
