Feature:  My user data

  @resetSchema
  @loadFixtures
  Scenario: As a user I should be able to get equipment list
    When I send a "GET" request to "/equipment"
    Then the response status code should be 200
    And the response should be in JSON
    And the response should contain json:
    """
    {
        "@context": "\/contexts\/Equipment",
        "@id": "\/equipment",
        "@type": "hydra:Collection",
        "hydra:member": [
            {
                "@id": "\/equipment\/1",
                "@type": "Equipment",
                "id": 1,
                "name": "computer",
                "user": "\/users\/2"
            },
            @...@
        ],
        "hydra:totalItems": 34,
        "hydra:view": {
            "@id": "\/equipment?page=1",
            "@type": "hydra:PartialCollectionView",
            "hydra:first": "\/equipment?page=1",
            "hydra:last": "\/equipment?page=2",
            "hydra:next": "\/equipment?page=2"
        }
    }
    """