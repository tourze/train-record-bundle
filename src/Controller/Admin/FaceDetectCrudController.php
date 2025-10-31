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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\TrainRecordBundle\Entity\FaceDetect;

/**
 * @extends AbstractCrudController<FaceDetect>
 */
#[AdminCrud(
    routePath: '/train-record/face-detect',
    routeName: 'train_record_face_detect',
)]
#[IsGranted(attribute: 'ROLE_ADMIN')]
final class FaceDetectCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FaceDetect::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('人脸检测记录')
            ->setEntityLabelInPlural('人脸检测记录管理')
            ->setSearchFields(['confidence', 'similarity'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(NumericFilter::new('confidence', '检测置信度'))
            ->add(NumericFilter::new('similarity', '相似度评分'))
            ->add(DateTimeFilter::new('createTime', '检测时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnDetail();

        yield AssociationField::new('session', '学习会话')
            ->setRequired(true)
        ;

        yield TextareaField::new('imageData', '图像数据')
            ->hideOnIndex()
            ->setMaxLength(16777215)
            ->setHelp('Base64编码的图像数据')
        ;

        yield NumberField::new('confidence', '检测置信度')
            ->setNumDecimals(2)
            ->setHelp('人脸检测的置信度，范围0-100')
        ;

        yield NumberField::new('similarity', '相似度评分')
            ->setNumDecimals(2)
            ->setHelp('与注册人脸的相似度，范围0-100')
        ;

        yield DateTimeField::new('createTime', '检测时间')
            ->hideOnForm()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
        ;
    }
}
