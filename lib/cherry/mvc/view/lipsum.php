<?php

namespace Cherry\Mvc\View;
use cherry\mvc\view;
class LipsumView extends Base{

    private $raw = <<<EOF
        Non eram nescius, Brute, cum, quae summis ingeniis exquisitaque doctrina philosophi Graeco sermone tractavissent, ea Latinis litteris mandaremus, fore ut hic noster labor in varias reprehensiones incurreret. nam quibusdam, et iis quidem non admodum indoctis, totum hoc displicet philosophari. quidam autem non tam id reprehendunt, si remissius agatur, sed tantum studium tamque multam operam ponendam in eo non arbitrantur. erunt etiam, et ii quidem eruditi Graecis litteris, contemnentes Latinas, qui se dicant in Graecis legendis operam malle consumere. postremo aliquos futuros suspicor, qui me ad alias litteras vocent, genus hoc scribendi, etsi sit elegans, personae tamen et dignitatis esse negent.
        Contra quos omnis dicendum breviter existimo. Quamquam philosophiae quidem vituperatoribus satis responsum est eo libro, quo a nobis philosophia defensa et collaudata est, cum esset accusata et vituperata ab Hortensio. qui liber cum et tibi probatus videretur et iis, quos ego posse iudicare arbitrarer, plura suscepi veritus ne movere hominum studia viderer, retinere non posse. Qui autem, si maxime hoc placeat, moderatius tamen id volunt fieri, difficilem quandam temperantiam postulant in eo, quod semel admissum coerceri reprimique non potest, ut propemodum iustioribus utamur illis, qui omnino avocent a philosophia, quam his, qui rebus infinitis modum constituant in reque eo meliore, quo maior sit, mediocritatem desiderent.
        Sive enim ad sapientiam perveniri potest, non paranda nobis solum ea, sed fruenda etiam [sapientia] est; sive hoc difficile est, tamen nec modus est ullus investigandi veri, nisi inveneris, et quaerendi defatigatio turpis est, cum id, quod quaeritur, sit pulcherrimum. etenim si delectamur, cum scribimus, quis est tam invidus, qui ab eo nos abducat? sin laboramus, quis est, qui alienae modum statuat industriae? nam ut Terentianus Chremes non inhumanus, qui novum vicinum non vult 'fodere aut arare aut aliquid ferre denique' -- non enim illum ab industria, sed ab inliberali labore deterret --, sic isti curiosi, quos offendit noster minime nobis iniucundus labor.
        Iis igitur est difficilius satis facere, qui se Latina scripta dicunt contemnere. in quibus hoc primum est in quo admirer, cur in gravissimis rebus non delectet eos sermo patrius, cum idem fabellas Latinas ad verbum e Graecis expressas non inviti legant. quis enim tam inimicus paene nomini Romano est, qui Ennii Medeam aut Antiopam Pacuvii spernat aut reiciat, quod se isdem Euripidis fabulis delectari dicat, Latinas litteras oderit?
        Synephebos ego, inquit, potius Caecilii aut Andriam Terentii quam utramque Menandri legam?
        A quibus tantum dissentio, ut, cum Sophocles vel optime scripserit Electram, tamen male conversam Atilii mihi legendam putem, de quo Lucilius: 'ferreum scriptorem', verum, opinor, scriptorem tamen, ut legendus sit. rudem enim esse omnino in nostris poetis aut inertissimae segnitiae est aut fastidii delicatissimi. mihi quidem nulli satis eruditi videntur, quibus nostra ignota sunt. an 'Utinam ne in nemore . . .' nihilo minus legimus quam hoc idem Graecum, quae autem de bene beateque vivendo a Platone disputata sunt, haec explicari non placebit Latine?
        Quid? si nos non interpretum fungimur munere, sed tuemur ea, quae dicta sunt ab iis quos probamus, eisque nostrum iudicium et nostrum scribendi ordinem adiungimus, quid habent, cur Graeca anteponant iis, quae et splendide dicta sint neque sint conversa de Graecis? nam si dicent ab illis has res esse tractatas, ne ipsos quidem Graecos est cur tam multos legant, quam legendi sunt. quid enim est a Chrysippo praetermissum in Stoicis? legimus tamen Diogenem, Antipatrum, Mnesarchum, Panaetium, multos alios in primisque familiarem nostrum Posidonium. quid? Theophrastus mediocriterne delectat, cum tractat locos ab Aristotele ante tractatos? quid? Epicurei num desistunt de isdem, de quibus et ab Epicuro scriptum est et ab antiquis, ad arbitrium suum scribere? quodsi Graeci leguntur a Graecis isdem de rebus alia ratione compositis, quid est, cur nostri a nostris non legantur?
        Quamquam, si plane sic verterem Platonem aut Aristotelem, ut verterunt nostri poetae fabulas, male, credo, mererer de meis civibus, si ad eorum cognitionem divina illa ingenia transferrem. sed id neque feci adhuc nec mihi tamen, ne faciam, interdictum puto. locos quidem quosdam, si videbitur, transferam, et maxime ab iis, quos modo nominavi, cum inciderit, ut id apte fieri possit, ut ab Homero Ennius, Afranius a Menandro solet. Nec vero, ut noster Lucilius, recusabo, quo minus omnes mea legant. utinam esset ille Persius, Scipio vero et Rutilius multo etiam magis, quorum ille iudicium reformidans Tarentinis ait se et Consentinis et Siculis scribere. facete is quidem, sicut alia; sed neque tam docti tum erant, ad quorum iudicium elaboraret, et sunt illius scripta leviora, ut urbanitas summa appareat, doctrina mediocris.
        Ego autem quem timeam lectorem, cum ad te ne Graecis quidem cedentem in philosophia audeam scribere? quamquam a te ipso id quidem facio provocatus gratissimo mihi libro, quem ad me de virtute misisti. Sed ex eo credo quibusdam usu venire; ut abhorreant a Latinis, quod inciderint in inculta quaedam et horrida, de malis Graecis Latine scripta deterius. quibus ego assentior, dum modo de isdem rebus ne Graecos quidem legendos putent. res vero bonas verbis electis graviter ornateque dictas quis non legat? nisi qui se plane Graecum dici velit, ut a Scaevola est praetore salutatus Athenis Albucius.
EOF;
    private $paras = array();
    private $contents = null;

    function __construct($paras = 5,$format = 'HTML') {
        $data = explode("\n", $this->raw);
        foreach($data as $row) {
            $this->paras[] = trim($row);
        }
        $pout = array();
        for($n = 0; $n < $paras; $n++) {
            $para = $this->paras[rand(0,count($this->paras)-1)];
            if ($format == 'HTML') {
                $pout[] = '<p>'.$para.'</p>';
            } else {
                $pout[] = $para;
            }
        }
        $this->contents = join("\n",$pout);
    }

    function getViewContents() {
        return $this->contents;

    }

    function load($foo) {
        echo $this->contents;
    }
}
