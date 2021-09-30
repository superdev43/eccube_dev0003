<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\MailMagazine4\Controller;

use Eccube\Controller\AbstractController;
use Plugin\CustomShipping\Repository\ConfigRepository;
use Plugin\MailMagazine4\Entity\MailMagazineTemplate;
use Plugin\MailMagazine4\Repository\MailMagazineTemplateRepository;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Plugin\MailMagazine4\Form\Type\MailMagazineTemplateEditType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;

class MailMagazineTemplateController extends AbstractController
{

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var MailMagazineTemplateRepository
     */
    protected $mailMagazineTemplateRepository;

    /**
     * MailMagazineTemplateController constructor.
     *
     * @param MailMagazineTemplateRepository $mailMagazineTemplateRepository
     */
    public function __construct(
        MailMagazineTemplateRepository $mailMagazineTemplateRepository,
        ConfigRepository $configRepository
    ) {
        $this->mailMagazineTemplateRepository = $mailMagazineTemplateRepository;
        $this->configRepository = $configRepository;
    }

    /**
     * 一覧表示.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/template", name="plugin_mail_magazine_template")
     * @Template("@MailMagazine4/admin/template_list.twig")
     */
    public function index()
    {
        $templateList = $this->mailMagazineTemplateRepository->findAll();

        return [
            'TemplateList' => $templateList,
        ];
    }

    /**
     * preview画面表示.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/template/{id}/preview",
     *     requirements={"id":"\d+"},
     *     name="plugin_mail_magazine_template_preview"
     * )
     * @Template("@MailMagazine4/admin/preview.twig")
     *
     * @param MailMagazineTemplate $mailMagazineTemplate
     *
     * @return array
     */
    public function preview(MailMagazineTemplate $mailMagazineTemplate)
    {
        // プレビューページ表示
        return [
            'Template' => $mailMagazineTemplate,
        ];
    }

    /**
     * メルマガテンプレートを論理削除.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/template/{id}/delete",
     *     requirements={"id":"\d+"},
     *     name="plugin_mail_magazine_template_delete",
     *     methods={"POST"}
     * )
     *
     * @param MailMagazineTemplate $mailMagazineTemplate
     *
     * @return RedirectResponse
     */
    public function delete(MailMagazineTemplate $mailMagazineTemplate)
    {
        // POSTかどうか判定
        // パラメータ$idにマッチするデータが存在するか判定
        // POSTかつ$idに対応するdtb_mailmagazine_templateのレコードがあれば、del_flg = 1に設定して更新
        try {
            $this->mailMagazineTemplateRepository->delete($mailMagazineTemplate);
            $this->entityManager->flush();
            $this->addSuccess('admin.delete.complete', 'admin');
        } catch (\Exception $e) {
            $this->addError('admin.delete.failed', 'admin');
        }

        // メルマガテンプレート一覧へリダイレクト
        return $this->redirect($this->generateUrl('plugin_mail_magazine_template'));
    }


    /**
     * @Route("/%eccube_admin_route%/mail_maga/upload", name="mail_magazine_admin_upload")
     */
    public function upload(Request $request)
    {
        //upload.php
        $userDataPath = 'eccube_shop/html/user_data/';
        $fileRoute = $userDataPath.'/assets/img/';
        $fieldname = "upload";
        if (isset($_FILES['upload']['name'])) {
            $file = $_FILES['upload']['tmp_name'];
            $file_name = $_FILES['upload']['name'];
            $file_name_array = explode(".", $file_name);
            $extension = end($file_name_array);
            $new_image_name = rand() . '.' . $extension;
            $fullNamePath = $fileRoute . $new_image_name;
            if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off") {
                $protocol = "https://";
            } else {
                $protocol = "http://";
            }
            // chmod('upload', 0777);
            $allowed_extension = array("jpg", "gif", "png");
            if (in_array($extension, $allowed_extension)) {
                move_uploaded_file($file, $fullNamePath);
                $function_number = $_GET['CKEditorFuncNum'];
                $url = $protocol.$_SERVER["HTTP_HOST"].'/'.$fullNamePath;
                $message = '';
                echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($function_number, '$url', '$message');</script>";
            }
        }

    }





    /**
     * テンプレート編集画面表示.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/template/{id}/edit",
     *     requirements={"id":"\d+"},
     *     name="plugin_mail_magazine_template_edit"
     * )
     * @Template("@MailMagazine4/admin/template_edit.twig")
     *
     * @param MailMagazineTemplate $mailMagazineTemplate
     *
     * @return array
     */
    public function edit(MailMagazineTemplate $mailMagazineTemplate)
    {
        // formの作成
        $form = $this->formFactory
            ->createBuilder(MailMagazineTemplateEditType::class, $mailMagazineTemplate)
            ->getForm();

        return [
            'form' => $form->createView(),
            'Template' => $mailMagazineTemplate,
        ];
    }

    /**
     * テンプレート編集確定処理.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/template/commit/{id}",
     *     requirements={"id":"\d+"},
     *     name="plugin_mail_magazine_template_commit",
     *     methods={"POST"}
     * )
     * @Template("@MailMagazine4/admin/template_edit.twig")
     *
     * @param Request $request
     * @param int $id
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function commit(Request $request, $id = null)
    {
        $Template = $id ? $this->mailMagazineTemplateRepository->find($id) : new MailMagazineTemplate();

        // データが存在しない場合はメルマガテンプレート一覧へリダイレクト
        if (is_null($Template)) {
            $this->addError('admin.mailmagazine.template.data.notfound', 'admin');

            return $this->redirect($this->generateUrl('plugin_mail_magazine_template'));
        }

        // Formを取得
        $builder = $this->formFactory->createBuilder(MailMagazineTemplateEditType::class, $Template);
        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // 入力項目確認処理を行う.
            // エラーであれば元の画面を表示する

            if (!$form->isValid()) {
                $this->addError('admin.flash.register_failed', 'admin');

                return [
                    'form' => $form->createView(),
                    'Template' => $Template,
                ];
            }

            try {
                $this->mailMagazineTemplateRepository->save($Template);
                $this->entityManager->flush();
                // 成功時のメッセージを登録する
                $this->addSuccess('admin.mailmagazine.template.save.complete', 'admin');
            } catch (\Exception $e) {
                $this->addError('admin.mailmagazine.template.save.failure', 'admin');

                return [
                    'form' => $form->createView(),
                    'Template' => $Template,
                ];
            }
        }

        // メルマガテンプレート一覧へリダイレクト
        return $this->redirect($this->generateUrl('plugin_mail_magazine_template'));
    }

    /**
     * メルマガテンプレート登録画面を表示する.
     *
     * @Route("/%eccube_admin_route%/plugin/mail_magazine/template/regist", name="plugin_mail_magazine_template_regist")
     * @Template("@MailMagazine4/admin/template_edit.twig")
     *
     * @return array
     */
    public function regist()
    {
        $Template = new MailMagazineTemplate();

        // formの作成
        $form = $this->formFactory
            ->createBuilder(MailMagazineTemplateEditType::class, $Template)
            ->getForm();

        return [
            'form' => $form->createView(),
            'Template' => $Template,
        ];
    }
}
