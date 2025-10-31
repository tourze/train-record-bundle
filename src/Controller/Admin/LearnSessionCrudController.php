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
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\TrainRecordBundle\Entity\LearnSession;

/**
 * @extends AbstractCrudController<LearnSession>
 */
#[AdminCrud(
    routePath: '/train-record/learn-session',
    routeName: 'train_record_learn_session',
)]
#[IsGranted(attribute: 'ROLE_ADMIN')]
final class LearnSessionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LearnSession::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('学习会话记录')
            ->setEntityLabelInPlural('学习会话记录管理')
            ->setSearchFields(['sessionId', 'createdFromUa'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('active', '会话状态'))
            ->add(NumericFilter::new('totalDuration', '总时长'))
            ->add(DateTimeFilter::new('firstLearnTime', '开始时间'))
            ->add(DateTimeFilter::new('finishTime', '结束时间'))
            ->add(TextFilter::new('sessionId', '会话ID'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnDetail();

        yield AssociationField::new('student', '学员')
            ->setRequired(true)
        ;

        yield AssociationField::new('registration', '报名记录')
            ->setRequired(true)
        ;

        yield AssociationField::new('lesson', '课程')
            ->setRequired(true)
        ;

        yield TextField::new('sessionId', '会话ID')
            ->setMaxLength(128)
            ->hideOnIndex()
            ->setHelp('用于标识会话的唯一ID')
        ;

        yield DateTimeField::new('firstLearnTime', '开始时间')
            ->setRequired(true)
        ;

        yield DateTimeField::new('finishTime', '结束时间');

        yield NumberField::new('totalDuration', '总时长(秒)')
            ->setNumDecimals(0)
            ->hideOnIndex()
        ;

        yield BooleanField::new('active', '会话状态')
            ->renderAsSwitch(false)
            ->setHelp('true表示会话进行中，false表示已结束')
        ;

        yield TextField::new('createdFromUa', '用户代理')
            ->hideOnIndex()
            ->setMaxLength(65535)
            ->setHelp('创建会话时的用户代理信息')
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
