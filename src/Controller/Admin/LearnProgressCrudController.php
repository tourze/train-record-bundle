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
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\TrainRecordBundle\Entity\LearnProgress;
use Tourze\TrainRecordBundle\Enum\LessonLearnStatus;

/**
 * @extends AbstractCrudController<LearnProgress>
 */
#[AdminCrud(
    routePath: '/train-record/learn-progress',
    routeName: 'train_record_learn_progress',
)]
#[IsGranted(attribute: 'ROLE_ADMIN')]
final class LearnProgressCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LearnProgress::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('学习进度记录')
            ->setEntityLabelInPlural('学习进度记录管理')
            ->setSearchFields(['userId'])
            ->setDefaultSort(['updateTime' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $statusChoices = [];
        foreach (LessonLearnStatus::cases() as $status) {
            $statusChoices[$status->getLabel()] = $status->value;
        }

        return $filters
            ->add(NumericFilter::new('progress', '进度百分比'))
            ->add(NumericFilter::new('watchedDuration', '已观看时长'))
            ->add(BooleanFilter::new('isCompleted', '是否完成'))
            ->add(DateTimeFilter::new('lastUpdateTime', '最后更新时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnDetail();

        yield TextField::new('userId', '用户ID')
            ->setRequired(true)
        ;

        yield AssociationField::new('course', '课程')
            ->setRequired(true)
        ;

        yield AssociationField::new('lesson', '课时')
            ->setRequired(true)
        ;

        yield NumberField::new('progress', '进度百分比')
            ->setNumDecimals(2)
            ->setHelp('0-100之间的数值')
        ;

        yield NumberField::new('watchedDuration', '已观看时长(秒)')
            ->setNumDecimals(4)
        ;

        yield NumberField::new('effectiveDuration', '有效时长(秒)')
            ->setNumDecimals(4)
            ->hideOnIndex()
        ;

        yield BooleanField::new('isCompleted', '是否完成')
            ->renderAsSwitch(false)
        ;

        yield NumberField::new('qualityScore', '质量评分')
            ->setNumDecimals(2)
            ->hideOnIndex()
        ;

        yield DateTimeField::new('lastUpdateTime', '最后更新时间');

        yield TextField::new('lastUpdateDevice', '最后更新设备')
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
