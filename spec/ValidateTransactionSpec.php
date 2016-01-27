<?php
namespace spec\groupcash\php;

use groupcash\php\Application;
use rtens\scrut\Assert;
use spec\groupcash\php\fakes\FakeCryptoService;
use spec\groupcash\php\fakes\FakeKeyService;

/**
 * Each backer of a coin is responsible for validating its transactions to confirm the new owner and avoid
 * double-spending of a single coin. The new coin consist of the original coin, the public key of the new owner
 * and, if existing, a fingerprint of the previous coin.
 *
 * @property Assert assert <-
 */
class ValidateTransactionSpec {

    function firstTransaction() {
        $app = new Application(new FakeKeyService('key'), new FakeCryptoService());

        $coins = $app->issueCoins('my promise', 'public backer', 42, 1, 'admin encrypted with foo', 'foo');
        $transferred = $app->transferCoins($coins, 'public first', 'backer');

        $validated = $app->validateTransaction($transferred[0], 'public backer', 'backer encrypted with foo', 'foo');
        $this->assert->equals($app->decode([$validated]), [[
            'content' => [
                'coin' => $coins[0],
                'to' => 'public first'
            ],
            'signer' => 'public backer',
            'signature' => 'a018ff97fcccb75ee63ab14019499033 signed with backer'
        ]]);
    }
}