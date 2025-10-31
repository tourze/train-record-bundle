<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;
use Tourze\TrainRecordBundle\Enum\InvalidTimeReason;
use Tourze\TrainRecordBundle\Enum\StudyTimeStatus;

/**
 * @extends AbstractCrudController<EffectiveStudyRecord>
 */
#[AdminCrud(
    routePath: '/train-record/effective-study-record',
    routeName: 'train_record_effective_study_record',
)]
#[IsGranted(attribute: 'ROLE_ADMIN')]
final class EffectiveStudyRecordCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EffectiveStudyRecord::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('有效学时记录')
            ->setEntityLabelInPlural('有效学时记录管理')
            ->setSearchFields(['userId', 'description', 'reviewComment', 'reviewedBy'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $statusChoices = [];
        foreach (StudyTimeStatus::cases() as $status) {
            $statusChoices[$status->getLabel()] = $status->value;
        }

        $invalidReasonChoices = ['无' => null];
        foreach (InvalidTimeReason::cases() as $reason) {
            $invalidReasonChoices[$reason->getLabel()] = $reason->value;
        }

        return $filters
            ->add(TextFilter::new('userId', '用户ID'))
            ->add(ChoiceFilter::new('status', '状态')->setChoices($statusChoices))
            ->add(ChoiceFilter::new('invalidReason', '无效原因')->setChoices($invalidReasonChoices))
            ->add(NumericFilter::new('qualityScore', '质量评分'))
            ->add(BooleanFilter::new('includeInDailyTotal', '计入日统计'))
            ->add(BooleanFilter::new('studentNotified', '学员已通知'))
            ->add(DateTimeFilter::new('studyDate', '学习日期'))
            ->add(TextFilter::new('reviewedBy', '审核人'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnDetail();

        yield TextField::new('userId', '用户ID')
            ->setRequired(true)
            ->setMaxLength(64)
        ;

        yield AssociationField::new('session', '学习会话')
            ->setRequired(true)
        ;

        yield DateTimeField::new('studyDate', '学习日期')
            ->setRequired(true)
        ;

        yield DateTimeField::new('startTime', '开始时间')
            ->setRequired(true)
        ;

        yield DateTimeField::new('endTime', '结束时间')
            ->setRequired(true)
        ;

        yield NumberField::new('totalDuration', '总时长(秒)')
            ->setNumDecimals(2)
            ->hideOnIndex()
        ;

        yield NumberField::new('effectiveDuration', '有效时长(秒)')
            ->setNumDecimals(2)
        ;

        yield NumberField::new('invalidDuration', '无效时长(秒)')
            ->setNumDecimals(2)
            ->hideOnIndex()
        ;

        $statusField = EnumField::new('status', '状态')
            ->setRequired(true)
        ;
        $statusField->setEnumCases(StudyTimeStatus::cases());
        yield $statusField;

        $invalidReasonField = EnumField::new('invalidReason', '无效原因')
            ->hideOnIndex()
        ;
        $invalidReasonField->setEnumCases(InvalidTimeReason::cases());
        yield $invalidReasonField;

        yield TextareaField::new('description', '描述')
            ->hideOnIndex()
        ;

        yield NumberField::new('qualityScore', '质量评分')
            ->setNumDecimals(1)
            ->hideOnIndex()
        ;

        yield NumberField::new('focusScore', '专注评分')
            ->setNumDecimals(2)
            ->hideOnIndex()
        ;

        yield NumberField::new('interactionScore', '交互评分')
            ->setNumDecimals(2)
            ->hideOnIndex()
        ;

        yield NumberField::new('continuityScore', '连续性评分')
            ->setNumDecimals(2)
            ->hideOnIndex()
        ;

        yield TextareaField::new('reviewComment', '审核意见')
            ->hideOnIndex()
        ;

        yield TextField::new('reviewedBy', '审核人')
            ->hideOnIndex()
        ;

        yield DateTimeField::new('reviewTime', '审核时间')
            ->hideOnIndex()
        ;

        yield BooleanField::new('includeInDailyTotal', '计入日统计')
            ->renderAsSwitch(false)
        ;

        yield BooleanField::new('studentNotified', '学员已通知')
            ->renderAsSwitch(false)
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

    public function createEntity(string $entityFqcn): EffectiveStudyRecord
    {
        $record = new EffectiveStudyRecord();
        $record->setStudyDate(new \DateTimeImmutable());
        // Status will be set through the form, default is handled in Entity constructor
        $record->setIncludeInDailyTotal(false);
        $record->setStudentNotified(false);

        return $record;
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        // 自动设置更新时间
        $entityInstance->setUpdateTime(new \DateTimeImmutable());

        parent::updateEntity($entityManager, $entityInstance);
    }
}
