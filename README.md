O cenário
=========

Imagine a seguinte situação:

1. Temos um participante que precisa fazer uma requisição HTTP qualquer.
2. Temos um participante responsável especificamente pela requisição.
3. Temos que testar o participante que precisa fazer a requisição HTTP.
4. Temos que testar o participante que vai fazer a requisição HTTP.

O problema
==========

Testar o participante que precisa fazer a requisição HTTP é muito simples. O problema é testar o participante que fará, efetivamente, a requisição HTTP.

Entre os problemas enumeráveis, estão:

1. Estamos sem conexão com internet.
2. Não temos controle sobre a resposta àquela requisição.

Existem 5 princípios, F.I.R.S.T., que fazem com que testar requisições HTTP se tornem um tanto complicadas:

**F** -- *Fast* - Os testes devem ser rápidos. Obviamente, fazer requisições reais não é nada rápido. De fato, o tempo de resposta do servidor, se estiver sobrecarregado, influenciará negativamente no tempo de execução dos testes.

**I** -- *Isolates* - Os motivos de falha devem ser óbvios. Se uma sobrecarga no servidor causar uma não resposta, ou uma resposta diferente da esperada, como saberemos identificar, de forma óbvia, o motivo da falha? O simples fato de estar fazendo uma requisição HTTP já nos dá dois motivos possíveis de falha:

* Falha no código que estamos testando.
* Falha no servidor que está recebendo a requisição. Nesse caso, nosso teste falhou, mas nosso código pode estar correto.

**R** -- *Repeatable* - Devemos poder repetir os testes, em qualquer ordem, em qualquer momento. Isso significa que, independentemente de termos, ou não, uma conexão com internet, os testes devem ser executáveis e o momento em que estamos executando não devem influenciar nos resultados do teste.

**S** -- *Self-validating* - Nenhuma intervenção manual deve ser requerida.
**T** -- *Timely* - Deve acontecer antes do código a ser testado.

A ideia
=======

Um dos recursos mais bacanas, e também mais poderosos do PHP, é a possibilidade de se criar stream wrappers e, com isso, ter um manipulador para diversos tipos de protocolo, mesmo que seja um protocolo que tenhamos inventado.

Para isso, algumas funções são necessárias:

[stream_wrapper_register()](http://php.net/manual/en/function.stream-wrapper-register.php)
[stream_wrapper_unregister()](http://php.net/manual/en/function.stream-wrapper-unregister.php)
[stream_context_create()](http://php.net/manual/en/function.stream-context-create.php)
[stream_context_get_options()](http://php.net/manual/en/function.stream-context-get-options.php)

O problema, contudo, da função stream_wrapper_register, é que ela aceita apenas nomes de classes. Não dá para passar uma instância de um objeto, ou seja, ao criar um wrapper para o protocolo HTTP, o PHP cuidará de criar, automaticamente, uma instância da classe com o nome passado para a função.

Como não temos acesso à instância criada, como podemos testá-la?

[Proxy](http://en.wikipedia.org/wiki/Proxy_pattern)
---------------------------------------------------

O padrão de design Proxy, como descrito no livro Design Patterns, resolve exatamente esse tipo de problema. Um participante servirá como substituto para um outro participante, oferecendo controle mais fino sobre ele.

A interface da classe que servirá como stream wrapper é a seguinte:

    <?php
    abstract class StreamWrapper
    {
        public $context;
    
        /**
         *
         * @return bool
         */
        public function dir_closedir()
        {
        }
    
        /**
         *
         * @param string $path
         * @param int $options
         * @return bool
         */
        public function dir_opendir($path, $options)
        {
        }
    
        /**
         *
         * @return string
         */
        public function dir_readdir()
        {
        }
    
        /**
         *
         * @return bool
         */
        public function dir_rewinddir()
        {
        }
    
        /**
         *
         * @param string $path
         * @param int $mode
         * @param int $options
         * @return bool
         */
        public function mkdir($path, $mode, $options)
        {
        }
    
        /**
         *
         * @param string $path_from
         * @param string $path_to
         * @return bool
         */
        public function rename($path_from, $path_to)
        {
        }
    
        /**
         *
         * @param string $path
         * @param int $options
         * @return bool
         */
        public function rmdir($path, $options)
        {
        }
    
        /**
         *
         * @param int $cast_as
         * @return resource
         */
        public function stream_cast($cast_as)
        {
        }
    
        public function stream_close()
        {
        }
    
        /**
         *
         * @return bool
         */
        public function stream_eof()
        {
        }
    
        /**
         *
         * @return bool
         */
        public function stream_flush()
        {
        }
    
        /**
         *
         * @param mode $operation
         * @return bool
         */
        public function stream_lock($operation)
        {
        }
    
        /**
         *
         * @param int $path
         * @param int $option
         * @param int $var
         * @return bool
         */
        public function stream_metadata($path, $option, $var)
        {
        }
    
        /**
         *
         * @param string $path
         * @param string $mode
         * @param int $options
         * @param string $opened_path
         * @return bool
         */
        public function stream_open($path, $mode, $options, &$opened_path)
        {
        }
    
        /**
         * @param int $count
         * @return string
         */
        public function stream_read($count)
        {
        }
    
        /**
         * @param int $offset
         * @param int $whence
         * @return bool
         */
        public function stream_seek($offset, $whence = SEEK_SET)
        {
        }
    
        /**
         * @param int $option
         * @param int $arg1
         * @param int $arg2
         * @return bool
         */
        public function stream_set_option($option, $arg1, $arg2)
        {
        }
    
        /**
         * @return array
         */
        public function stream_stat()
        {
        }
    
        /**
         * @return int
         */
        public function stream_tell()
        {
        }
    
        /**
         * @param int $new_size
         * @return bool
         */
        public function stream_truncate($new_size)
        {
        }
    
        /**
         * @param string $data
         * @return int
         */
        public function stream_write($data)
        {
        }
    
        /**
         * @param string $path
         * @return bool
         */
        public function unlink($path)
        {
        }
    
        /**
         * @param string $path
         * @param int $flags
         * @return array
         */
        public function url_stat($path, $flags)
        {
        }
    }

O mais interessante, contudo, é que não precisamos implementar toda a interface. Para ilustrar o uso de StreamWrapper + Proxy + Mock, vou criar alguns participantes:

`Gists` - Um participante que obterá detalhes sobre os gists de um usuário do Github.
`Gist` - Uma entidade que representa os dados de um Gist.
`HttpRequest` - O participante responsável pela requisição HTTP.
`StreamWrapperProxy` - O proxy que permitirá que os testes sejam feitos.

Além disso, a [documentação da API do Github para os Gists](http://developer.github.com/v3/gists/) será fundamental.
