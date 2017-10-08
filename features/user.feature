Feature:  My user data

  @resetSchema
  @loadFixtures
  Scenario: As a user I should be able to get users list
    When I send a "GET" request to "/users"
    Then the response status code should be 200
    And the response should be in JSON
    And the response should contain json:
    """
    {
        "@context": "\/contexts\/User",
        "@id": "\/users",
        "@type": "hydra:Collection",
        "hydra:member": [
            {
                "@id": "\/users\/1",
                "@type": "User",
                "id": 1,
                "name": "admin",
                "surname": "surname",
                "email": "admin@tsh.pl",
                "equipments": [
                    "\/equipment\/32",
                    "\/equipment\/33"
                ],
                "project": []
            },
            @...@
        ],
        "hydra:totalItems": 21
    }
    """