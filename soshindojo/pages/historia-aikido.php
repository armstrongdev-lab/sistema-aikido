<?php
require_once __DIR__ . '/../includes/header.php'; // reutiliza seu header dinâmico
?>

<style>
    .pagina-historia {
        max-width: 1000px;
        margin: 0 auto;
        padding: 40px 20px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.8;
        color: #333;
    }

    .pagina-historia h1 {
        font-size: 2.2rem;
        color: #1a1a1a;
        border-left: 6px solid #a00;
        padding-left: 15px;
        margin-bottom: 25px;
    }

    .pagina-historia p {
        font-size: 1.1rem;
        margin-bottom: 18px;
        text-align: justify;
    }

    .divider {
        margin: 60px 0;
        border: none;
        border-top: 2px dashed #ccc;
        width: 80%;
    }

    @media (max-width: 768px) {
        .pagina-historia {
            padding: 20px 15px;
        }

        .pagina-historia h1 {
            font-size: 1.6rem;
        }

        .pagina-historia p {
            font-size: 1rem;
        }
    }
</style>

<main class="pagina-historia">
    <section>
        <h1>História do Aikido</h1>
        <p>
            O Aikido é uma arte marcial japonesa moderna fundada por Morihei Ueshiba (O-Sensei) no início do século XX. 
            Nascido da fusão entre técnicas de defesa tradicionais e uma filosofia de paz, o Aikido busca resolver conflitos 
            sem agressividade, utilizando a energia do oponente a favor da harmonia.
        </p>
        <p>
            Com raízes no Daito Ryu Aiki-Jujutsu, o Aikido evoluiu para uma prática onde o autoconhecimento e o controle emocional 
            são tão importantes quanto a técnica física. Não há competições: o foco está no crescimento pessoal e no respeito ao próximo.
        </p>
        <p>
            Hoje, o Aikido é praticado mundialmente por pessoas de todas as idades, transmitindo valores de paz, disciplina, 
            respeito e equilíbrio interior.
        </p>
    </section>

    <hr class="divider">

    <section>
        <h1>História do Soshin Dojo</h1>
        <p>
            O Soshin Dojo nasceu do sonho de criar um espaço dedicado ao estudo profundo e verdadeiro do Aikido. O nome “Soshin” 
            significa “mente pura”, refletindo o espírito com que cada treino é conduzido: foco, dedicação e integridade.
        </p>
        <p>
            Desde sua fundação, o dojo mantém como pilares o respeito mútuo, a busca constante pela excelência técnica e a formação 
            de seres humanos melhores através da prática do Aikido. O ambiente acolhedor e disciplinado atrai praticantes comprometidos 
            com o aprimoramento físico e mental.
        </p>
        <p>
            O Soshin Dojo é, acima de tudo, um caminho de transformação — onde cada passo no tatame representa um passo na jornada 
            interior de seus alunos.
        </p>
    </section>
</main>

<?php include('../includes/footer.php'); ?>
