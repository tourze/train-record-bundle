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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\TrainRecordBundle\Entity\LearnArchive;
use Tourze\TrainRecordBundle\Enum\ArchiveFormat;
use Tourze\TrainRecordBundle\Enum\ArchiveStatus;

/**
 * @extends AbstractCrudController<LearnArchive>
 */
#[AdminCrud(
    routePath: '/train-record/learn-archive',
    routeName: 'train_record_learn_archive',
)]
#[IsGranted(attribute: 'ROLE_ADMIN')]
final class LearnArchiveCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LearnArchive::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('学习归档记录')
            ->setEntityLabelInPlural('学习归档记录管理')
            ->setSearchFields(['userId', 'archivePath'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('archiveFormat', '归档格式')
                ->setChoices($this->getArchiveFormatChoices()))
            ->add(ChoiceFilter::new('archiveStatus', '归档状态')
                ->setChoices($this->getArchiveStatusChoices()))
            ->add(NumericFilter::new('fileSize', '文件大小'))
            ->add(DateTimeFilter::new('archiveTime', '归档时间'))
            ->add(TextFilter::new('userId', '用户ID'))
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

        yield TextField::new('archivePath', '归档路径')
            ->hideOnIndex()
        ;

        $archiveFormatField = EnumField::new('archiveFormat', '归档格式')
            ->setRequired(true)
        ;
        $archiveFormatField->setEnumCases(ArchiveFormat::cases());
        yield $archiveFormatField;

        yield NumberField::new('fileSize', '文件大小(字节)')
            ->hideOnIndex()
        ;

        $archiveStatusField = EnumField::new('archiveStatus', '归档状态')
            ->setRequired(true)
        ;
        $archiveStatusField->setEnumCases(ArchiveStatus::cases());
        yield $archiveStatusField;

        yield DateTimeField::new('archiveTime', '归档时间');

        yield DateTimeField::new('expiryTime', '过期时间')
            ->hideOnIndex()
        ;

        yield NumberField::new('totalSessions', '总会话数')
            ->hideOnIndex()
        ;

        yield NumberField::new('totalEffectiveTime', '总有效时长')
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

    /**
     * 获取归档格式选项
     * @return array<string, string>
     */
    private function getArchiveFormatChoices(): array
    {
        $choices = [];
        foreach (ArchiveFormat::cases() as $format) {
            $choices[$format->getLabel()] = $format->value;
        }

        return $choices;
    }

    /**
     * 获取归档状态选项
     * @return array<string, string>
     */
    private function getArchiveStatusChoices(): array
    {
        $choices = [];
        foreach (ArchiveStatus::cases() as $status) {
            $choices[$status->getLabel()] = $status->value;
        }

        return $choices;
    }
}
