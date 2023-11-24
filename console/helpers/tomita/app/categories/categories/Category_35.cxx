#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 35 - Перевозка зерна

Transp -> AnyWord<kwtype="перевезти">;
What -> "зерновоз" | "зерно" | "зерновые";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_35);