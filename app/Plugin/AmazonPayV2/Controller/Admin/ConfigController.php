<?php
/*   __________________________________________________
    |  Obfuscated by YAK Pro - Php Obfuscator  2.0.3   |
    |              on 2021-07-20 10:45:26              |
    |    GitHub: https://github.com/pk-fr/yakpro-po    |
    |__________________________________________________|
*/
namespace Plugin\AmazonPayV2\Controller\Admin;use Eccube\Controller\AbstractController;use Plugin\AmazonPayV2\Form\Type\Admin\ConfigType;use Plugin\AmazonPayV2\Repository\ConfigRepository;use Symfony\Component\Form\FormError;use Symfony\Component\Routing\Annotation\Route;use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;use Symfony\Component\HttpFoundation\Request;use Symfony\Component\Validator\Constraints as Assert;use Symfony\Component\Validator\Validator\ValidatorInterface;class ConfigController extends AbstractController{protected $validator;protected $configRepository;public function __construct(ValidatorInterface $validator, ConfigRepository $configRepository){$this->validator = $validator;$this->configRepository = $configRepository;}    /**
     * @Route("/%eccube_admin_route%/amazon_pay_v2/config", name="amazon_pay_v2_admin_config")
     * @Template("@AmazonPayV2/admin/config.twig")
     */
public function index(Request $request){goto vchll;mnWNx:Cxi9E:goto Gb4I2;SVrPn:if (!is_file($privateKeyPath) || file_exists($privateKeyPath) === false) {goto gaNPW;}goto AdmkO;uQgsW:gaNPW:goto v3pky;N_v0R:V3p15:goto A4Rhf;Zff6t:$Config = $form->getData();goto rcN3g;p6CGz:WP1Ss:goto JTjBI;wpItO:$form['prod_key']->addError(new FormError('本番環境切り替えキーは有効なキーではありません。'));goto JAkkI;Ofg7W:$this->entityManager->persist($Config);goto xY_HX;arFES:$form['prod_key']->addError(new FormError($messages[0]));goto qyp0S;GsADy:return ['form' => $form->createView(), 'testAccount' => $testAccount];goto VyG_V;W01uY:if ($errors->count() != 0) {goto Cxi9E;}goto Zhcet;Y5LSi:if (mb_substr($Config->getPrivateKeyPath(), 0, 1) == '/') {goto vXr3y;}goto SVrPn;Zhcet:if (password_verify($prod_key, '$2y$10$m3aYrihBaIKarlrmI39tGORK4fFBC7cWoSLFy6jkMpT7IduYVsVtO')) {goto FW10w;}goto wpItO;JAkkI:FW10w:goto jHh8t;Li49i:$prod_key = $Config->getProdKey();goto ynvxh;RZ93V:$this->addSuccess('amazon_pay_v2.admin.save.success', 'admin');goto lP5gg;HnphD:$form = $this->createForm(ConfigType::class, $Config);goto Eopj4;xY_HX:$this->entityManager->flush($Config);goto RZ93V;A4Rhf:if (!($Config->getAmazonAccountMode() == $this->eccubeConfig['amazon_pay_v2']['account_mode']['owned'])) {goto vtFDq;}goto anRPi;xcnSD:$form['private_key_path']->addError(new FormError('プライベートキーパスの先頭に"/"は利用できません'));goto xXb9y;YlMLH:q0RKz:goto arFES;ahF1T:Dmqzx:goto M3iIu;MF0cP:vXr3y:goto xcnSD;xXb9y:goto WP1Ss;goto uQgsW;Zcgji:if (!($form->isSubmitted() && $form->isValid())) {goto yEmJ_;}goto Ofg7W;vchll:$Config = $this->configRepository->get(true);goto HnphD;ynvxh:$errors = $this->validator->validate($prod_key, [new Assert\NotBlank()]);goto W01uY;Eopj4:$form->handleRequest($request);goto HDV8V;M3iIu:$testAccount = $this->eccubeConfig['amazon_pay_v2']['test_account'];goto GsADy;v3pky:$form['private_key_path']->addError(new FormError('プライベートキーファイルが見つかりません。'));goto p6CGz;JTjBI:vtFDq:goto Zcgji;Gb4I2:foreach ($errors as $error) {$messages[] = $error->getMessage();rv0xz:}goto YlMLH;HDV8V:if (!($form->isSubmitted() && $form->isValid())) {goto Dmqzx;}goto Zff6t;aEd8s:yEmJ_:goto ahF1T;lP5gg:return $this->redirectToRoute('amazon_pay_v2_admin_config');goto aEd8s;anRPi:$privateKeyPath = $this->getParameter('kernel.project_dir') . '/' . $Config->getPrivateKeyPath();goto Y5LSi;rcN3g:if (!($Config->getEnv() == $this->eccubeConfig['amazon_pay_v2']['env']['prod'])) {goto V3p15;}goto Li49i;qyp0S:Qe7N6:goto N_v0R;AdmkO:goto WP1Ss;goto MF0cP;jHh8t:goto Qe7N6;goto mnWNx;VyG_V:}}