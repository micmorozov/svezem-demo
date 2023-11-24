#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 45 - Квартирный переезд
What -> "квартирный" | "домашний";
Transp -> "переезд";

Fact -> What Transp;

S -> Fact interp(Category.Category_45);
