import {
  Column,
  Flex,
  Heading,
  Text,
  Button,
  Card,
  Icon,
  Schema
} from "@once-ui-system/core";
import { baseURL } from "@/resources";

export default function TermsPage() {
  return (
    <Flex fillWidth horizontal="center" paddingY="xl">
      <Schema
        as="webPage"
        baseURL={baseURL}
        title="Правила портала - CloudMasters"
        description="Правила использования портала CloudMasters"
        path="/terms"
      />
      
      <Flex fillWidth horizontal="center">
        <Flex
          background="page"
          border="neutral-alpha-weak"
          radius="m-4"
          shadow="l"
          padding="4"
          horizontal="center"
          zIndex={1}
        >
          <Card padding="xl" radius="l" shadow="l" maxWidth="l">
            <Column gap="l" horizontal="center">
              <Flex gap="m" vertical="center">
                <Icon name="document" size="l" />
                <Heading variant="display-strong-s">Правила портала</Heading>
              </Flex>
              
              <Text variant="body-default-s" onBackground="neutral-weak" align="center">
                Общие правила использования портала CloudMasters
              </Text>

              <Column gap="m" fillWidth>
                <Heading variant="display-strong-xs">1. Общие положения</Heading>
                <Text variant="body-default-s" onBackground="neutral-weak">
                  Настоящие правила определяют порядок использования портала CloudMasters и устанавливают 
                  права и обязанности пользователей при работе с системой.
                </Text>

                <Heading variant="display-strong-xs">2. Регистрация и аккаунты</Heading>
                <Text variant="body-default-s" onBackground="neutral-weak">
                  При регистрации пользователь обязуется предоставить достоверную информацию. 
                  Каждый пользователь может иметь только один аккаунт.
                </Text>

                <Heading variant="display-strong-xs">3. Безопасность</Heading>
                <Text variant="body-default-s" onBackground="neutral-weak">
                  Пользователь несет ответственность за сохранность своих учетных данных. 
                  Запрещается передавать доступ к аккаунту третьим лицам.
                </Text>

                <Heading variant="display-strong-xs">4. Контент</Heading>
                <Text variant="body-default-s" onBackground="neutral-weak">
                  Пользователи могут создавать и редактировать контент в рамках своих прав доступа. 
                  Запрещается размещение незаконного или оскорбительного контента.
                </Text>

                <Heading variant="display-strong-xs">5. Конфиденциальность</Heading>
                <Text variant="body-default-s" onBackground="neutral-weak">
                  Администрация портала обязуется защищать персональные данные пользователей 
                  в соответствии с действующим законодательством.
                </Text>

                <Heading variant="display-strong-xs">6. Ответственность</Heading>
                <Text variant="body-default-s" onBackground="neutral-weak">
                  Пользователи несут ответственность за свои действия в системе. 
                  Администрация оставляет за собой право ограничить доступ при нарушении правил.
                </Text>
              </Column>

              <Button 
                variant="secondary" 
                href="/auth/register"
                prefixIcon="arrow-left"
              >
                Вернуться к регистрации
              </Button>
            </Column>
          </Card>
        </Flex>
      </Flex>
    </Flex>
  );
} 