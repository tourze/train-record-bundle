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
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\TrainRecordBundle\Entity\LearnBehavior;
use Tourze\TrainRecordBundle\Enum\BehaviorType;

/**
 * @extends AbstractCrudController<LearnBehavior>
 */
#[AdminCrud(
    routePath: '/train-record/learn-behavior',
    routeName: 'train_record_learn_behavior',
)]
#[IsGranted(attribute: 'ROLE_ADMIN')]
final class LearnBehaviorCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LearnBehavior::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('学习行为记录')
            ->setEntityLabelInPlural('学习行为记录管理')
            ->setSearchFields(['behaviorData', 'userId', 'ipAddress', 'suspiciousReason'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(50)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $behaviorTypeChoices = [];
        foreach (BehaviorType::cases() as $type) {
            $behaviorTypeChoices[$type->getLabel()] = $type->value;
        }

        return $filters
            ->add(ChoiceFilter::new('behaviorType', '行为类型')
                ->setChoices($behaviorTypeChoices))
            ->add(BooleanFilter::new('isSuspicious', '可疑行为'))
            ->add(TextFilter::new('userId', '用户ID'))
            ->add(TextFilter::new('ipAddress', 'IP地址'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnDetail();

        yield AssociationField::new('session', '学习会话')
            ->setRequired(true)
        ;

        $behaviorTypeField = EnumField::new('behaviorType', '行为类型')
            ->setRequired(true)
        ;
        $behaviorTypeField->setEnumCases(BehaviorType::cases());
        yield $behaviorTypeField;

        yield CodeEditorField::new('behaviorData', '行为数据')
            ->setLanguage('js')
            ->hideOnIndex()
            ->setHelp('JSON格式的行为详细数据')
            ->formatValue(static function (?array $value): string {
                if (null === $value) {
                    return '';
                }
                $encoded = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                return false !== $encoded ? $encoded : '';
            })
        ;

        yield TextField::new('userId', '用户ID')
            ->hideOnForm()
            ->setHelp('冗余字段，便于查询')
        ;

        yield TextField::new('videoTimestamp', '视频时间戳')
            ->hideOnForm()
            ->setHelp('视频播放时间点（秒）')
        ;

        yield TextField::new('deviceFingerprint', '设备指纹')
            ->hideOnForm()
            ->hideOnIndex()
            ->setHelp('设备唯一标识')
        ;

        yield TextField::new('ipAddress', 'IP地址')
            ->hideOnForm()
            ->setHelp('用户IP地址')
        ;

        yield BooleanField::new('isSuspicious', '可疑行为')
            ->renderAsSwitch(false)
            ->hideOnForm()
            ->setHelp('系统自动检测的可疑行为')
        ;

        yield TextField::new('suspiciousReason', '可疑原因')
            ->hideOnForm()
            ->hideOnIndex()
            ->setHelp('被标记为可疑行为的原因')
        ;

        yield CodeEditorField::new('metadata', '元数据')
            ->setLanguage('js')
            ->hideOnForm()
            ->hideOnIndex()
            ->setHelp('额外的元数据信息')
            ->formatValue(static function (?array $value): string {
                if (null === $value) {
                    return '';
                }
                $encoded = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                return false !== $encoded ? $encoded : '';
            })
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
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
