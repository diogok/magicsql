<?php

include 'MagicSql.class.php';

// Supondo que o banco de dados esta criado 
// Como se conectar
$con = Connection::Mysql($host,$user,$password,$database);

// $con usa o PDO, é só procurar no site do PHP.net/pdo
$resultado = $con->query($sql)->fetchAll();

// Exemplo que tenha uma tabela noticias com 
// ( id (auto_increment), titulo , texto ,id_autor, primary key(id) )
// e uma autores com ( id , nome, primary key(id) )
// E importante estar bem definido no banco de dados

// Criando o Repositorio de autores
// Os parametros são MagicSql($conexao,$tabela);
$autorDB = new MagicSql($con,"autores");
// Conexao com noticias
$noticiasDB = new MagicSql($con,"noticias");

// Criando um objeto vazio
$autor = $autorDB->getNew();

// Inserindo um autor
$autor->nome = "Diogo";
$autorDB->insert($autor);

// Inserindo uma noticia para o autor
$noticia = $noticiasDB->getNew();
$noticia->titulo = "MagicSQL";
$noticia->texto = "Faz MAGICA!";
$noticia->id_autor = $autor->id ;

$noticiasDB->insert($noticia);

$n2 = $noticiasDB->getNew();
$n2->titulo = "Como esta";
$n2->texto = "Esta ficando bem legal, neh?";
$n2->id_autor = $autor->id ;

$noticiasDB->insert($n2);

// Como achar o Diogo no banco de dados, pelo ID
$diogo = $autorDB->get(1);
// Ou pelo nome, retorna lista
$diogo = $autorDB->get("nome","Diogo");
// Mas assim ele retorna um array
$autor = $diogo[0];
// acho que assim tbm vai
$diogo = $autorDB->get("nome","Diogo")->get(0);

// Agora vou buscar as noticias do Diogo
$noticias = $noticiasDB->get("id_autor",$autor->id_autor);

// Vou pegar so a segunda noticia
$n = $noticias[1];

// Vou atualiza-la
$n->texto = "Atualiza hoje";

// Just like magic!
$noticiasDB->update($n);

// Acho que deu para entender o INSERIR/ATUALIZAR

// Opções de forma de pesquisa
$notica = $noticiasDB->get($id); // Retorna 1 com esse id
$noticias = $noticiasDB->get("campo","valor") ; // retorna array onde campo = valor
$noticias = $noticiasDB->get("campo1,campo2","valor1,valor2"); // retorna array onde campo1 = valor1 , campo2 = valor2 
$campos = array ( "campo1", "campo2") ;
$valores = array ( "valor1" , "valor2") ;
$noticias = $noticiasDB->get($campos,$valores); // da no mesmo que o anterior
$order = "Titulo ASC";
$noticias = $noticiasDB->get($campos,$valores,$order); // Com ordem de titulos
$noticias = $noticiasDB->get($campos,$valores,null,"1"); // Sem ordem com limit 1
$noticias = $noticiasDB->get($campos,$valores,$order,$limit); // Com ordem e Limit

// Se precisar de algo mais quick and dirty
$where = "id_autor = 1, and titulo like 'The%'";
$noticias = $noticiasDB->select($where);
// No mesmo modelo do get, order e limit opcional
$noticias = $noticiasDB->select($where,$oder,$limit);

// Ufa, basicamente é isso!
// Que esta faltando ?
// A o mais importante, o MagicRepository
// O MagicRepository já monta  a estrutura toda do BD
// E faz join sózinho
// (geralmente funciona)

// Iniciando um Repo
$con = Connection::Mysql($host,$user,$password,$database);
$rep  = new MagicRepository($con);

// Exemplo que tenha uma tabela noticias com 
// ( id (auto_increment), titulo , texto ,id_autor, primary key(id) )
// e uma autores com ( id , nome, primary key(id) )

$notDb = $rep->table("noticias"); // da o mesmo que o MagicSql agora, mas fazendo join.

$noticias = $rep->table("noticias")->select(); // seleciona todos as noticias, com seus autores
//passa a ser disponivel o autor da noticias
// é sempre many-to-many
var_dump($noticias[0]->autor);
var_dump($noticias[0]->autor[0]);
var_dump($noticias[0]->autor[0]->nome);

// geralmente o contrário também funciona
$autor = $rep->table("autor")->get("nome","diogo");
var_dump($autor[0]->noticias[2]->titulo);

?>
