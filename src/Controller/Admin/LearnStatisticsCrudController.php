<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\TrainRecordBundle\Entity\LearnStatistics;
use Tourze\TrainRecordBundle\Enum\StatisticsPeriod;
use Tourze\TrainRecordBundle\Enum\StatisticsType;

/**
 * @extends AbstractCrudController<LearnStatistics>
 */
#[AdminCrud(
    routePath: '/train-record/learn-statistics',
    routeName: 'train_record_learn_statistics',
)]
#[IsGranted(attribute: 'ROLE_ADMIN')]
final class LearnStatisticsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LearnStatistics::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('学习统计记录')
            ->setEntityLabelInPlural('学习统计记录管理')
            ->setSearchFields(['statisticsType', 'statisticsPeriod'])
            ->setDefaultSort(['statisticsDate' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $typeChoices = [];
        foreach (StatisticsType::cases() as $type) {
            $typeChoices[$type->getLabel()] = $type->value;
        }

        $periodChoices = [];
        foreach (StatisticsPeriod::cases() as $period) {
            $periodChoices[$period->getLabel()] = $period->value;
        }

        return $filters
            ->add(ChoiceFilter::new('statisticsType', '统计类型')->setChoices($typeChoices))
            ->add(ChoiceFilter::new('statisticsPeriod', '统计周期')->setChoices($periodChoices))
            ->add(DateTimeFilter::new('statisticsDate', '统计日期'))
            ->add(NumericFilter::new('totalUsers', '总用户数'))
            ->add(NumericFilter::new('totalSessions', '总会话数'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnDetail();

        $statisticsTypeField = EnumField::new('statisticsType', '统计类型')
            ->setRequired(true)
        ;
        $statisticsTypeField->setEnumCases(StatisticsType::cases());
        yield $statisticsTypeField;

        $periodField = EnumField::new('statisticsPeriod', '统计周期')
            ->setRequired(true)
        ;
        $periodField->setEnumCases(StatisticsPeriod::cases());
        yield $periodField;

        yield DateTimeField::new('statisticsDate', '统计日期')
            ->setRequired(true)
        ;

        yield NumberField::new('totalUsers', '总用户数');

        yield NumberField::new('activeUsers', '活跃用户数');

        yield NumberField::new('totalSessions', '总会话数');

        yield NumberField::new('totalDuration', '总学习时长')
            ->setNumDecimals(2)
            ->hideOnIndex()
        ;

        yield NumberField::new('effectiveDuration', '有效学习时长')
            ->setNumDecimals(2)
            ->hideOnIndex()
        ;

        yield NumberField::new('anomalyCount', '异常数量')
            ->hideOnIndex()
        ;

        yield NumberField::new('completionRate', '完成率(%)')
            ->setNumDecimals(2)
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
