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
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\TrainRecordBundle\Entity\LearnLog;
use Tourze\TrainRecordBundle\Enum\LearnAction;

/**
 * @extends AbstractCrudController<LearnLog>
 */
#[AdminCrud(
    routePath: '/train-record/learn-log',
    routeName: 'train_record_learn_log',
)]
#[IsGranted(attribute: 'ROLE_ADMIN')]
final class LearnLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LearnLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('学习日志记录')
            ->setEntityLabelInPlural('学习日志记录管理')
            ->setSearchFields(['message', 'createdFromIp'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(50)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $actionChoices = [];
        foreach (LearnAction::cases() as $action) {
            $actionChoices[$action->getLabel()] = $action->value;
        }

        return $filters
            ->add(ChoiceFilter::new('action', '操作类型')
                ->setChoices($actionChoices))
            ->add(TextFilter::new('createdFromIp', 'IP地址'))
            ->add(DateTimeFilter::new('createTime', '操作时间'))
            ->add(TextFilter::new('message', '日志消息'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnDetail();

        yield AssociationField::new('learnSession', '学习会话')
            ->setRequired(true)
        ;

        $actionField = EnumField::new('action', '操作类型')
            ->setRequired(true)
        ;
        $actionField->setEnumCases(LearnAction::cases());
        yield $actionField;

        yield DateTimeField::new('createTime', '操作时间')
            ->setRequired(true)
        ;

        yield TextField::new('createdFromIp', 'IP地址')
            ->setMaxLength(45)
            ->setHelp('IPv4或IPv6地址')
        ;

        yield TextField::new('createdFromUa', '用户代理')
            ->setMaxLength(500)
        ;

        yield TextareaField::new('message', '日志消息')
            ->hideOnIndex()
            ->setMaxLength(1000)
        ;

        // JSON字段完全隐藏，避免类型转换错误
        // yield TextareaField::new('context', '附加数据')
        //     ->onlyOnDetail()
        //     ->setHelp('JSON格式的附加信息')
        //     ->formatValue(function ($value) {
        //         return is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $value;
        //     })
        // ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }
}
