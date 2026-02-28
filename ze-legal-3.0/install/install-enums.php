<?php
if (!defined('ABSPATH')) {
    exit;
}

function ze_install_enums_iniciais() {
    global $wpdb;

    $table = $wpdb->prefix . 'ze_tb_enums';

    // ===============================
    // DEFINIÇÃO OFICIAL DOS ENUMS
    // ===============================
    $enums = [

        // ===============================
        // tb_colaboradores.ds_camiseta
        // ===============================
        [
            'ds_enum' => 'P',
            'tb_alvo_enum' => 'tb_colaboradores',
            'campo_alvo_enum' => 'ds_camiseta',
            'num_orden_enum' => 1,
            'status_enum' => 1,
        ],
        [
            'ds_enum' => 'M',
            'tb_alvo_enum' => 'tb_colaboradores',
            'campo_alvo_enum' => 'ds_camiseta',
            'num_orden_enum' => 2,
            'status_enum' => 1,
        ],
        [
            'ds_enum' => 'G',
            'tb_alvo_enum' => 'tb_colaboradores',
            'campo_alvo_enum' => 'ds_camiseta',
            'num_orden_enum' => 3,
            'status_enum' => 1,
        ],
        [
            'ds_enum' => 'GG',
            'tb_alvo_enum' => 'tb_colaboradores',
            'campo_alvo_enum' => 'ds_camiseta',
            'num_orden_enum' => 4,
            'status_enum' => 1,
        ],

        // ===============================
        // tb_colaboradores.ds_tipo_colaborador
        // ===============================
        [
            'ds_enum' => 'CONVENCIONAL',
            'tb_alvo_enum' => 'tb_colaboradores',
            'campo_alvo_enum' => 'ds_tipo_colaborador',
            'num_orden_enum' => 1,
            'status_enum' => 1,
        ],
        [
            'ds_enum' => 'CARTORIO',
            'tb_alvo_enum' => 'tb_colaboradores',
            'campo_alvo_enum' => 'ds_tipo_colaborador',
            'num_orden_enum' => 2,
            'status_enum' => 1,
        ],
        [
            'ds_enum' => 'MOTORISTA',
            'tb_alvo_enum' => 'tb_colaboradores',
            'campo_alvo_enum' => 'ds_tipo_colaborador',
            'num_orden_enum' => 3,
            'status_enum' => 1,
        ],

        ['ds_enum'=>'Fiat','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'marca','num_orden_enum'=>1,'status_enum'=>1],
        ['ds_enum'=>'Volkswagen','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'marca','num_orden_enum'=>2,'status_enum'=>1],
        ['ds_enum'=>'Chevrolet','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'marca','num_orden_enum'=>3,'status_enum'=>1],
        ['ds_enum'=>'Ford','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'marca','num_orden_enum'=>4,'status_enum'=>1],
        ['ds_enum'=>'Toyota','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'marca','num_orden_enum'=>5,'status_enum'=>1],
        ['ds_enum'=>'Renault','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'marca','num_orden_enum'=>6,'status_enum'=>1],
        ['ds_enum'=>'Hyundai','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'marca','num_orden_enum'=>7,'status_enum'=>1],
        ['ds_enum'=>'Nissan','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'marca','num_orden_enum'=>8,'status_enum'=>1],
        ['ds_enum'=>'Jeep','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'marca','num_orden_enum'=>9,'status_enum'=>1],
        ['ds_enum'=>'Mercedes-Benz','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'marca','num_orden_enum'=>10,'status_enum'=>1],
        ['ds_enum'=>'Iveco','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'marca','num_orden_enum'=>11,'status_enum'=>1],
        ['ds_enum'=>'Volkswagen Caminhões e Ônibus','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'marca','num_orden_enum'=>12,'status_enum'=>1],
        ['ds_enum'=>'Scania','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'marca','num_orden_enum'=>13,'status_enum'=>1],
        ['ds_enum'=>'Volvo','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'marca','num_orden_enum'=>14,'status_enum'=>1],

        // ==================================================
        // tb_veiculos.modelo
        // ==================================================
        ['ds_enum'=>'Uno','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>1,'status_enum'=>1],
        ['ds_enum'=>'Mobi','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>2,'status_enum'=>1],
        ['ds_enum'=>'Argo','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>3,'status_enum'=>1],
        ['ds_enum'=>'Cronos','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>4,'status_enum'=>1],
        ['ds_enum'=>'Siena','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>5,'status_enum'=>1],
        ['ds_enum'=>'Strada','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>6,'status_enum'=>1],
        ['ds_enum'=>'Fiorino','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>7,'status_enum'=>1],
        ['ds_enum'=>'Ducato','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>8,'status_enum'=>1],
        ['ds_enum'=>'Gol','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>9,'status_enum'=>1],
        ['ds_enum'=>'Voyage','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>10,'status_enum'=>1],
        ['ds_enum'=>'Polo','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>11,'status_enum'=>1],
        ['ds_enum'=>'Virtus','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>12,'status_enum'=>1],
        ['ds_enum'=>'Saveiro','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>13,'status_enum'=>1],
        ['ds_enum'=>'Amarok','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>14,'status_enum'=>1],
        ['ds_enum'=>'Kombi','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>15,'status_enum'=>1],
        ['ds_enum'=>'Crafter','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>16,'status_enum'=>1],
        ['ds_enum'=>'Celta','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>17,'status_enum'=>1],
        ['ds_enum'=>'Classic','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>18,'status_enum'=>1],
        ['ds_enum'=>'Onix','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>19,'status_enum'=>1],
        ['ds_enum'=>'Prisma','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>20,'status_enum'=>1],
        ['ds_enum'=>'Spin','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>21,'status_enum'=>1],
        ['ds_enum'=>'S10','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>22,'status_enum'=>1],
        ['ds_enum'=>'Trailblazer','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>23,'status_enum'=>1],
        ['ds_enum'=>'Montana','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>24,'status_enum'=>1],
        ['ds_enum'=>'Ka','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>25,'status_enum'=>1],
        ['ds_enum'=>'Ka Sedan','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>26,'status_enum'=>1],
        ['ds_enum'=>'Fiesta','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>27,'status_enum'=>1],
        ['ds_enum'=>'Focus','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>28,'status_enum'=>1],
        ['ds_enum'=>'Ranger','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>29,'status_enum'=>1],
        ['ds_enum'=>'Transit','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>30,'status_enum'=>1],
        ['ds_enum'=>'Corolla','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>31,'status_enum'=>1],
        ['ds_enum'=>'Etios','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>32,'status_enum'=>1],
        ['ds_enum'=>'Hilux','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>33,'status_enum'=>1],
        ['ds_enum'=>'Hilux SW4','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>34,'status_enum'=>1],
        ['ds_enum'=>'Bandeirante','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>35,'status_enum'=>1],
        ['ds_enum'=>'Clio','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>36,'status_enum'=>1],
        ['ds_enum'=>'Logan','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>37,'status_enum'=>1],
        ['ds_enum'=>'Sandero','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>38,'status_enum'=>1],
        ['ds_enum'=>'Duster','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>39,'status_enum'=>1],
        ['ds_enum'=>'Master','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>40,'status_enum'=>1],
        ['ds_enum'=>'Kangoo','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>41,'status_enum'=>1],
        ['ds_enum'=>'HB20','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>42,'status_enum'=>1],
        ['ds_enum'=>'HB20S','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>43,'status_enum'=>1],
        ['ds_enum'=>'Creta','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>44,'status_enum'=>1],
        ['ds_enum'=>'Tucson','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>45,'status_enum'=>1],
        ['ds_enum'=>'March','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>46,'status_enum'=>1],
        ['ds_enum'=>'Versa','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>47,'status_enum'=>1],
        ['ds_enum'=>'Sentra','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>48,'status_enum'=>1],
        ['ds_enum'=>'Frontier','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>49,'status_enum'=>1],
        ['ds_enum'=>'Renegade','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>50,'status_enum'=>1],
        ['ds_enum'=>'Compass','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>51,'status_enum'=>1],
        ['ds_enum'=>'Sprinter','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>52,'status_enum'=>1],
        ['ds_enum'=>'Vito','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>53,'status_enum'=>1],
        ['ds_enum'=>'Accelo','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>54,'status_enum'=>1],
        ['ds_enum'=>'Atego','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>55,'status_enum'=>1],
        ['ds_enum'=>'Daily','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>56,'status_enum'=>1],
        ['ds_enum'=>'Tector','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>57,'status_enum'=>1],
        ['ds_enum'=>'Delivery','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>58,'status_enum'=>1],
        ['ds_enum'=>'Constellation','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>59,'status_enum'=>1],
        ['ds_enum'=>'Volksbus','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>60,'status_enum'=>1],
        ['ds_enum'=>'Série P','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>61,'status_enum'=>1],
        ['ds_enum'=>'Série G','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>62,'status_enum'=>1],
        ['ds_enum'=>'VM','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>63,'status_enum'=>1],
        ['ds_enum'=>'FH','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'modelo','num_orden_enum'=>64,'status_enum'=>1],

        // ==================================================
        // tb_veiculos.cor
        // ==================================================
        ['ds_enum'=>'Branco','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'cor','num_orden_enum'=>1,'status_enum'=>1],
        ['ds_enum'=>'Prata','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'cor','num_orden_enum'=>2,'status_enum'=>1],
        ['ds_enum'=>'Cinza','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'cor','num_orden_enum'=>3,'status_enum'=>1],
        ['ds_enum'=>'Preto','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'cor','num_orden_enum'=>4,'status_enum'=>1],
        ['ds_enum'=>'Azul','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'cor','num_orden_enum'=>5,'status_enum'=>1],
        ['ds_enum'=>'Vermelho','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'cor','num_orden_enum'=>6,'status_enum'=>1],
        ['ds_enum'=>'Verde','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'cor','num_orden_enum'=>7,'status_enum'=>1],
        ['ds_enum'=>'Amarelo','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'cor','num_orden_enum'=>8,'status_enum'=>1],
        ['ds_enum'=>'Bege','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'cor','num_orden_enum'=>9,'status_enum'=>1],
        ['ds_enum'=>'Marrom','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'cor','num_orden_enum'=>10,'status_enum'=>1],
        ['ds_enum'=>'Grafite','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'cor','num_orden_enum'=>11,'status_enum'=>1],
        ['ds_enum'=>'Dourado','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'cor','num_orden_enum'=>12,'status_enum'=>1],
        ['ds_enum'=>'Laranja','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'cor','num_orden_enum'=>13,'status_enum'=>1],
        ['ds_enum'=>'Vinho','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'cor','num_orden_enum'=>14,'status_enum'=>1],

        // ==================================================
        // tb_veiculos.ds_tipo
        // ==================================================
        ['ds_enum'=>'Automóvel','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'ds_tipo','num_orden_enum'=>1,'status_enum'=>1],
        ['ds_enum'=>'SUV','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'ds_tipo','num_orden_enum'=>2,'status_enum'=>1],
        ['ds_enum'=>'Camioneta cabine dupla','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'ds_tipo','num_orden_enum'=>3,'status_enum'=>1],
        ['ds_enum'=>'Camioneta cabine simples','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'ds_tipo','num_orden_enum'=>4,'status_enum'=>1],
        ['ds_enum'=>'Van de passageiros','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'ds_tipo','num_orden_enum'=>5,'status_enum'=>1],
        ['ds_enum'=>'Micro-ônibus','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'ds_tipo','num_orden_enum'=>6,'status_enum'=>1],
        ['ds_enum'=>'Ônibus','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'ds_tipo','num_orden_enum'=>7,'status_enum'=>1],
        ['ds_enum'=>'Furgão','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'ds_tipo','num_orden_enum'=>8,'status_enum'=>1],
        ['ds_enum'=>'Furgão baú','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'ds_tipo','num_orden_enum'=>9,'status_enum'=>1],
        ['ds_enum'=>'Caminhão leve','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'ds_tipo','num_orden_enum'=>10,'status_enum'=>1],
        ['ds_enum'=>'Caminhão carroceria','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'ds_tipo','num_orden_enum'=>11,'status_enum'=>1],
        ['ds_enum'=>'Caminhão baú','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'ds_tipo','num_orden_enum'=>12,'status_enum'=>1],

        // ==================================================
        // tb_veiculos.ds_combustivel
        // ==================================================
        ['ds_enum'=>'Flex','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'ds_combustivel','num_orden_enum'=>1,'status_enum'=>1],
        ['ds_enum'=>'Gasolina','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'ds_combustivel','num_orden_enum'=>2,'status_enum'=>1],
        ['ds_enum'=>'Álcool','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'ds_combustivel','num_orden_enum'=>3,'status_enum'=>1],
        ['ds_enum'=>'Diesel','tb_alvo_enum'=>'tb_veiculos','campo_alvo_enum'=>'ds_combustivel','num_orden_enum'=>4,'status_enum'=>1],
    
    ];

    // ===============================
    // PROCESSAMENTO
    // ===============================
    foreach ($enums as $enum) {

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id_enum 
                 FROM {$table}
                 WHERE ds_enum = %s
                   AND tb_alvo_enum = %s
                   AND campo_alvo_enum = %s
                 LIMIT 1",
                $enum['ds_enum'],
                $enum['tb_alvo_enum'],
                $enum['campo_alvo_enum']
            )
        );

        if (!$exists) {
            $wpdb->insert(
                $table,
                $enum,
                [
                    '%s', // ds_enum
                    '%s', // tb_alvo_enum
                    '%s', // campo_alvo_enum
                    '%d', // num_orden_enum
                    '%d', // status_enum
                ]
            );
        }
    }
}
