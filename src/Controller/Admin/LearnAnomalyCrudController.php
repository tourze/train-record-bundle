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
use Tourze\TrainRecordBundle\Entity\LearnAnomaly;
use Tourze\TrainRecordBundle\Enum\AnomalySeverity;
use Tourze\TrainRecordBundle\Enum\AnomalyStatus;
use Tourze\TrainRecordBundle\Enum\AnomalyType;

/**
 * @extends AbstractCrudController<LearnAnomaly>
 */
#[AdminCrud(
    routePath: '/train-record/learn-anomaly',
    routeName: 'train_record_learn_anomaly',
)]
#[IsGranted(attribute: 'ROLE_ADMIN')]
final class LearnAnomalyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LearnAnomaly::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('学习异常记录')
            ->setEntityLabelInPlural('学习异常记录管理')
            ->setSearchFields(['anomalyDescription', 'resolution'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('anomalyType', '异常类型')
                ->setChoices($this->getAnomalyTypeChoices()))
            ->add(ChoiceFilter::new('severity', '严重程度')
                ->setChoices($this->getAnomalySeverityChoices()))
            ->add(ChoiceFilter::new('status', '处理状态')
                ->setChoices($this->getAnomalyStatusChoices()))
            ->add(DateTimeFilter::new('detectTime', '检测时间'))
            ->add(TextFilter::new('resolvedBy', '处理人'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnDetail();

        yield AssociationField::new('session', '学习会话')
            ->setRequired(true)
        ;

        $anomalyTypeField = EnumField::new('anomalyType', '异常类型')
            ->setRequired(true)
        ;
        $anomalyTypeField->setEnumCases(AnomalyType::cases());
        yield $anomalyTypeField;

        yield TextareaField::new('anomalyDescription', '异常描述')
            ->hideOnIndex()
            ->setMaxLength(65535)
        ;

        $severityField = EnumField::new('severity', '严重程度')
            ->setRequired(true)
        ;
        $severityField->setEnumCases(AnomalySeverity::cases());
        yield $severityField;

        $statusField = EnumField::new('status', '处理状态')
            ->setRequired(true)
        ;
        $statusField->setEnumCases(AnomalyStatus::cases());
        yield $statusField;

        yield DateTimeField::new('detectTime', '检测时间')
            ->setFormTypeOption('html5', true)
            ->setFormTypeOption('widget', 'single_text')
            ->setRequired(true)
        ;

        yield TextField::new('resolvedBy', '处理人')
            ->hideOnIndex()
        ;

        yield DateTimeField::new('resolveTime', '处理时间')
            ->hideOnIndex()
        ;

        yield TextareaField::new('resolution', '解决方案')
            ->hideOnIndex()
            ->setMaxLength(1000)
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

    public function createEntity(string $entityFqcn): LearnAnomaly
    {
        $anomaly = new LearnAnomaly();
        $anomaly->setDetectTime(new \DateTimeImmutable());
        $anomaly->setStatus(AnomalyStatus::DETECTED);
        $anomaly->setSeverity(AnomalySeverity::MEDIUM);

        return $anomaly;
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        // 自动设置更新时间
        $entityInstance->setUpdateTime(new \DateTimeImmutable());

        // 如果状态变更为已解决，自动设置处理时间
        if (AnomalyStatus::RESOLVED === $entityInstance->getStatus() && null === $entityInstance->getResolveTime()) {
            $entityInstance->setResolveTime(new \DateTimeImmutable());

            // 设置当前用户为处理人
            $user = $this->getUser();
            if (null !== $user) {
                $entityInstance->setResolvedBy($user->getUserIdentifier());
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * 获取异常类型选项
     * @return array<string, string>
     */
    private function getAnomalyTypeChoices(): array
    {
        $choices = [];
        foreach (AnomalyType::cases() as $type) {
            $choices[$type->getLabel()] = $type->value;
        }

        return $choices;
    }

    /**
     * 获取异常严重程度选项
     * @return array<string, string>
     */
    private function getAnomalySeverityChoices(): array
    {
        $choices = [];
        foreach (AnomalySeverity::cases() as $severity) {
            $choices[$severity->getLabel()] = $severity->value;
        }

        return $choices;
    }

    /**
     * 获取异常状态选项
     * @return array<string, string>
     */
    private function getAnomalyStatusChoices(): array
    {
        $choices = [];
        foreach (AnomalyStatus::cases() as $status) {
            $choices[$status->getLabel()] = $status->value;
        }

        return $choices;
    }
}
